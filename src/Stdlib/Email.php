<?php

/**
 * Class to send email
 *
 * @author Ezra Obiwale <contact@ezraobiwale.com>
 * 
 * @todo Check why mixed messages (html & text) get sent but with no body
 * @todo Try some attachments
 * @see mail()
 */
class Email {

	protected $from, $to, $cc, $bcc, $replyTo, $attachments;
	private $sep, $html, $htmlCharset, $text, $textCharset, $headers, $sepOn;

	/**
	 * Class constructor
	 * 
	 * @param string $from Email address of the sender
	 * @param string $to Email address(es) of the recipient(s)
	 * @param string $cc Email address(es) to send carbon copy
	 * @param string $bcc Email address(es) to send best carbon copy
	 * @param string $replyTo Email address to reply to
	 */
	public function __construct($from = null, $to = null, $cc = null, $bcc = null, $replyTo = null) {
		$this->attachments = array();
		$this->sep = md5(time());
		$this->sepOn = false;
		$this->headers = null;
		$this->charset = 'utf-8';

		$this->from = $from;
		$this->to = $to;
		$this->cc = $cc;
		$this->bcc = $bcc;
		$this->replyTo = $replyTo;
	}

	/**
	 * Attach a file to the email
	 * @param string $filePath Path to the file
	 * @return \Email
	 * @throws Exception
	 */
	public function addAttachment($filePath) {
		if (!$file = fopen($filePath, 'r')) {
			throw new Exception('Cannot open file "' . $filePath . '"');
		}

		$this->attachments[$filePath] = chunk_split(base64_encode(fread($file, filesize($filePath))));
		return $this;
	}

	/**
	 * Attach multiple files to the email
	 * @param array $filePaths Array of paths to the files
	 * @return \Email
	 */
	public function setAttachments(array $filePaths) {
		foreach ($filePaths as $filePath) {
			$this->addAttachment($filePath);
		}

		return $this;
	}

	/**
	 * Set html content
	 * @param string $content
	 * @param array $options Keys may include [(string) charset [= "utf-8"], (boolean) autoSetText]
	 * @return \Email
	 */
	public function setHTML($content, array $options = array()) {
		$this->html = $content;
		$this->htmlCharset = (isset($options['charset'])) ? $options['charset'] : 'utf-8';
		if (@$options['autoSetText']) {
			$this->setText(strip_tags($this->html), $this->htmlCharset);
		}
		return $this;
	}

	/**
	 * Set text content
	 * @param string $content
	 * @return \Email
	 */
	public function setText($content, $charset = 'utf-8') {
		$this->text = $content;
		$this->textCharset = $charset;
		return $this;
	}

	/**
	 * Send the email
	 * @param string $subject
	 * @param array $options Keys may include [to, from, cc, bcc, replyTo].<br />
	 * If any is not set, the default is assumed.
	 * @return boolean
	 * @throws Exception
	 */
	public function send($subject, array $options = array()) {
		$to = $this->to;
		$from = $this->from;
		$cc = $this->cc;
		$bcc = $this->bcc;
		$replyTo = $this->replyTo;

		foreach ($options as $key => $value) {
			$$key = $value;
		}

		$this->createHeaders($from, $cc, $bcc, $replyTo);

		if (!empty($this->html)) {
			$message = $this->html;

			$endWithSeparator = false;
			if ($this->text || $this->attachments) {
				$this->headers("Content-type: multipart/" . (($this->attachments) ? 'mixed' : 'alternative') . "; charset=" . $this->htmlCharset);
				$endWithSeparator = true;
			}
			else {
				$this->headers("Content-type: text/html; charset=" . $this->htmlCharset);
				$this->headers('Content-Transfer-Encoding:8bit');
			}
			$this->headers("MIME-Version: 1.0");

			if (!empty($this->text)) {
				$this->separateHeaders();
				$this->headers("Content-type: text/plain; charset=" . $this->htmlCharset);
				$this->headers('Content-Transfer-Encoding:8bit');
				$this->headers($this->text);

				$this->separateHeaders();
				$this->headers("Content-type: text/html; charset=" . $this->htmlCharset);
				$this->headers('Content-Transfer-Encoding:8bit');
				$this->headers($message);
			}
		}
		elseif (!empty($this->text)) {
			$message = $this->text;
		}
		else {
			throw new Exception('Email content not set! Use either/both of setText() and setHTML()');
		}

		if (!empty($this->attachments)) {
			$this->setAttachmentHeader();
		}

		if ($endWithSeparator) $this->separateHeaders();

		return mail($to, $subject, $message, $this->headers);
	}

	public function getFrom() {
		return $this->from;
	}

	public function getTo() {
		return $this->to;
	}

	public function getCc() {
		return $this->cc;
	}

	public function getBcc() {
		return $this->bcc;
	}

	public function getReplyTo() {
		return $this->replyTo;
	}

	public function sendFrom($from, $receiveReplies = false) {
		$this->from = $from;
		if ($receiveReplies) $this->replyTo($from);
		return $this;
	}

	public function addTo($to) {
		$this->to = (!empty($this->to)) ? $this->to . ',' . $to : $to;
		return $this;
	}

	public function addCc($cc) {
		$this->cc = (!empty($this->cc)) ? $this->cc . ',' . $cc : $cc;
		return $this;
	}

	/**
	 * Adds a best carbon copy
	 * @param string $bcc Email address to send to
	 * @return \Email
	 */
	public function addBcc($bcc) {
		$this->bcc = (!empty($this->bcc)) ? $this->bcc . ',' . $bcc : $bcc;
		return $this;
	}

	/**
	 * Fetches the email address to receive replies from message
	 * @param string $replyTo
	 * @return \Email
	 */
	public function replyTo($replyTo) {
		$this->replyTo = $replyTo;
		return $this;
	}

	// -------------- private methods ---------------------

	private function createHeaders($from, $cc = array(), $bcc = array(), $replyTo = null) {
		if ($from) $this->headers('From: ' . $from);

		if (!is_array($cc)) {
			$cc = ($cc) ? array($cc) : array();
		}
		if (!is_array($bcc)) {
			$bcc = ($bcc) ? array($bcc) : array();
		}

		foreach ($cc as $c) {
			$this->headers("Cc: " . $c);
		}

		foreach ($bcc as $b) {
			$this->headers("Bcc: " . $b);
		}

		if ($replyTo) $this->headers("Reply-To: " . $replyTo);

		return $this;
	}

	private function setAttachmentHeader() {
		foreach ($this->attachments as $filePath => $attachment) {
			$this->separateHeaders();
			$this->headers('Content-type: multipart/mixed; ' .
					'name:"' . pathinfo($filePath, PATHINFO_FILENAME) . '"');
			$this->headers('Content-Transfer-Encoding:base64');
			$this->headers('Content-Disposition:attachment; ' .
					'filename:' . pathinfo($filePath, PATHINFO_FILENAME));
			$this->headers($attachment);
		}
		return $this;
	}

	private function headers($content) {
		if (!empty($this->headers)) {
			$this->headers .= "\r\n";

			$this->headers .= $content;
		}
		else {
			$this->headers = $content;
		}
		return $this;
	}

	private function separateHeaders() {
		if (!$this->sep) {
			$this->sep = md5(time());
		}
		if (!$this->sepOn) {
			$this->headers('boundary=' . $this->sep);
			$this->sepOn = true;
		}

		return $this->headers("\r\n" . '--' . $this->sep);
	}

}
