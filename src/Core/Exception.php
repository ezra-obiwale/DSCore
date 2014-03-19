<?php

namespace DScribe\Core;

/**
 * @todo Distinguish between warning and fatal errors
 */
class Exception extends \Exception {

    private $errorFile;
    private $errorLine;

    /**
     * Class constructor
     * @param string $message
     * @param boolean $noLayout Indicates whether to render with layout or not
     * @param boolean $push Indicates whether to push out the message
     */
    public function __construct($message = null, $noLayout = false, $push = true) {
        parent::__construct($message);
        if ($push)
            $this->push($this, $noLayout);
    }

    public function push(\Exception $ex = null, $noLayout = false) {
        $ex = ($ex === null) ? $this : $ex;

        if (!$noLayout) {
            $view = new \DScribe\View\View(false);
            $view->render($this->prepareOutput($ex));
        }
        else {
            echo $this->prepareOutput($ex);
        }
        exit;
    }

    final public function specifics($file, $line) {
        $this->errorFile = $file;
        $this->errorLine = $line;
    }

    private function prepareOutput(\Exception $ex) {
        $traceArray = $ex->getTrace();

        if (isset($traceArray[0]['args']) && isset($traceArray[0]['file']) && isset($traceArray[0]['file'])) {
            $ex = $this;
        }
        else if (is_object($traceArray[0]['args'][0])) {
            $ex = $traceArray[0]['args'][0];
        }
        $header = 'Exception';
        $message = $ex->getMessage();
        if ($this->errorFile && strtolower(Engine::getConfig('server', false)) === 'development') {
            $message .= '<div style="color:darkblue;margin-top:10px;font-size:smaller">' . $this->errorFile . ': ' . $this->errorLine . '</div>' . "\n";
        }
        ob_start();
        echo '<div style="padding:5px;font-family:arial;color:darkblue">';
        echo '<h1>' . $header . '</h1>';
        echo '<div style="border:1px solid #ccc;border-radius:5px;padding:5px;font-size:larger">';
        echo $message;
        echo '</div>';
        if (strtolower(Engine::getConfig('server', false)) === 'development') {
            $this->prepareTrace($ex);
        }
        echo '</div>';
        return ob_get_clean();
    }

    private function prepareTrace(\Exception $ex) {
        echo '<div style="background-color:#fff;padding:5px;border-radius:5px;border:1px solid #ccc;margin-top:10px">';
        echo '<div style="color:maroon;font-size:26px;margin:10px 0">Trace</div>';
        echo '<pre style="background-color:rgb(245,245,245);border-radius:0 15px;padding:5px;border:1px solid #ccc">';
        foreach ($ex->getTrace() as $trace) {
            if (isset($trace['function']) && in_array($trace['function'], array('errorHandler', 'exceptionHandler')))
                continue;

            echo '<div onmouseover="$(this).css({border:\'6px solid #0ce\',\'border-radius\':0,\'background-color\':\'#fff\'})" onmouseout="$(this).css({border:\'2px outset #ccc\',\'border-radius\':\'0 15px\',\'background-color\':\'#fff\'})" style="background-color:white;border:2px outset #ccc;margin:5px 0;padding:10px;border-radius:0 15px">';
            echo '<span style="color:darkblue;font-size:small;margin-bottom:5px">';

            if (!empty($trace['class']))
                echo $trace['class'] . $trace['type'];

            if (!empty($trace['function']))
                echo $trace['function'] . '(' . $this->parseArgs($trace['args']) . ')</span>';

            echo "\n";

            if (!empty($trace['file']))
                echo '<span style="color:maroon;font-size:smaller;">' . $trace['file'] . ': ' . $trace['line'] . "</span>\n";
            echo '</div>';
        }
        echo '</pre>';
        echo '</div>';
        echo '
<script>
	$(function(){
		$(a).css("border-radius":"0 15px");
	});
</script>
';
    }

    private function parseArgs(array $args) {
        $return = '';
        foreach ($args as $ky => $arg) {
            if (is_array($arg) || is_object($arg) || is_null($arg)) {
                $return .= strtoupper(gettype($arg));
            }
            else {
                if (is_string($arg)) {
                    if (strlen($arg) > 75)
                        $arg = 'LONG_STRING';
                    else
                        $arg = "'$arg'";
                }
                if (is_bool($arg))
                    $arg = ($arg) ? 'TRUE' : 'FALSE';
                $return .= $arg;
            }

            if ($ky < count($args) - 1) {
                $return .= ', ';
            }
        }
        return $return;
    }

}
