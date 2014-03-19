<?php

class Util {

    const FILES_ONLY = 1;
    const DIRS_ONLY = 2;
    const ALL = 3;
    const UPLOAD_ERROR_NO_FILE = 'No file found';
    const UPLOAD_ERROR_SIZE = 'Size of file is too big';
    const UPLOAD_ERROR_EXTENSION = 'File extension is not allowed';
    const UPLOAD_ERROR_PATH = 'Create path failed';
    const UPLOAD_ERROR_PERMISSION = 'Insufficient permission to save';
    const UPLOAD_ERROR_FAILED = 'Upload failed';
    const UPLOAD_SUCCESSFUL = 'File uploaded successfully';

    public static $uploadSuccess = self::UPLOAD_SUCCESSFUL;

    /**
     * Turns camelCasedString to under_scored_string
     * @param string $str
     * @return string
     */
    public static function camelTo_($str) {
        if (!is_string($str))
            return '';
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return "_" . strtolower($c[1]);');
        return preg_replace_callback('/([A-Z])/', $func, $str);
    }

    /**
     * Turns camelCasedString to hyphened-string
     * @param string $str
     * @param boolean $strtolower
     * @return string
     */
    public static function camelToHyphen($str, $strtolower = true) {
        if (!is_string($str))
            return '';
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return "-" . $c[1];');
        $str = preg_replace_callback('/([A-Z])/', $func, $str);
        return ($strtolower) ? strtolower($str) : $str;
    }

    public static function arrayValuesCamelTo(array &$array, $to) {
        $func = create_function('$c', 'return "' . $to . '" . strtolower($c[1]);');
        foreach ($array as &$value) {
            if (!is_string($value))
                continue;
            $value[0] = strtolower($value[0]);
            $value = preg_replace_callback('/([A-Z])/', $func, $value);
        }
        return $array;
    }

    /**
     * Turns camelCasedString to spaced out string
     * @param string $str
     * @return string
     */
    public static function camelToSpace($str) {
        if (!is_string($str))
            return '';
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return " " . $c[1];');
        return preg_replace_callback('/([A-Z])/', $func, $str);
    }

    /**
     * Turns under_scored_string to camelCasedString
     * @param string $str
     * @return string
     */
    public static function _toCamel($str) {
        if (!is_string($str))
            return '';
        return preg_replace_callback('/_([a-z])/', function($c) {
            return strtoupper($c[1]);
        }, $str);
    }

    /**
     * Turns hyphened-string to camelCasedString
     * @param string $str
     * @return string
     */
    public static function hyphenToCamel($str) {
        if (!is_string($str))
            return '';
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/-([a-z])/', $func, $str);
    }

    /**
     * Reads the required source directory
     * @param string $dir
     * @param int $return
     * @param string|array $extension
     * @param boolean $nameOnly Indicates whether to return full path of dirs/files or names only
     * @return array
     * @throws \Exception
     */
    public static function readDir($dir, $return = Util::ALL, $recursive = false, $extension = NULL, $nameOnly = false) {
        if (!is_dir($dir))
            return array(
                'error' => 'Directory "' . $dir . '" does not exist',
            );

        if (!is_array($extension) && !empty($extension)) {
            $extension = array($extension);
        }

        if (substr($dir, strlen($dir) - 1) !== DIRECTORY_SEPARATOR)
            $dir .= DIRECTORY_SEPARATOR;

        $toReturn = array('dirs' => array(), 'files' => array());
        try {
            $handle = opendir($dir);
            while ($current = readdir($handle)) {
                if (in_array($current, array('.', '..')))
                    continue;

                if (is_dir($dir . $current)) {
                    if (in_array($return, array(self::DIRS_ONLY, self::ALL))) {
                        $toReturn['dirs'][] = ($nameOnly) ? $current : $dir . $current;
                    }
                    if ($recursive) {
                        $toReturn = array_merge_recursive($toReturn, self::readDir($dir . $current, self::ALL, true));
                    }
                }
                else if (is_file($dir . $current) && in_array($return, array(self::FILES_ONLY, self::ALL))) {
                    $info = pathinfo($current);
                    if (empty($extension) || (is_array($extension) && in_array($info['extension'], $extension)))
                        $toReturn['files'][] = ($nameOnly) ? $current : $dir . $current;
                }
            }

            if ($return == self::ALL)
                return $toReturn;
            elseif ($return == self::DIRS_ONLY)
                return $toReturn['dirs'];
            elseif ($return == self::FILES_ONLY)
                return $toReturn['files'];
        }
        catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * Copies a directory to another location
     * @param string $source
     * @param string $destination
     * @param string $permission
     * @param boolean $recursive
     * @throws \Exception
     */
    public static function copyDir($source, $destination, $permission = 0777, $recursive = true) {
        if (substr($source, strlen($destination) - 1) !== DIRECTORY_SEPARATOR)
            $destination .= DIRECTORY_SEPARATOR;

        try {
            if (!is_dir($destination))
                mkdir($destination, $permission);

            $contents = self::readDir($source, self::ALL, $recursive, NULL);
            if (isset($contents['dirs'])) {
                foreach ($contents['dirs'] as $fullPath) {
                    @mkdir(str_replace(array($source, DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR), array($destination, DIRECTORY_SEPARATOR), $fullPath), $permission);
                }
            }

            if (isset($contents['files'])) {
                foreach ($contents['files'] as $fullPath) {
                    @copy($fullPath, str_replace(array($source, DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR), array($destination, DIRECTORY_SEPARATOR), $fullPath));
                }
            }
        }
        catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * Deletes a directory and all contents including subdirectories and files
     * 
     * @param string $file
     * @return boolean
     */
    public static function delDir($dir) {
        $all = self::readDir($dir, self::ALL, true, NULL);

        if (isset($all['files'])) {
            foreach ($all['files'] as $file) {
                if (is_array($file)) {
                    foreach ($file as $fil) {
                        if (!unlink($fil)) {
                            return false;
                        }
                    }
                }
                else {
                    if (!unlink($file)) {
                        return false;
                    }
                }
            }
        }

        if (isset($all['dirs'])) {
            foreach (array_reverse($all['dirs']) as $_dir) {
                if (is_array($_dir)) {
                    foreach ($_dir as $_dr) {
                        if (!rmdir($_dr)) {
                            return false;
                        }
                    }
                }
                else {
                    if (!rmdir($_dir)) {
                        return false;
                    }
                }
            }
        }

        return rmdir($dir);
    }

    /**
     * Shortens a string to desired length
     * @param string $str String to shorten
     * @param integer $length Length of the string to return
     * @param string $break String to replace the truncated part with
     * @return string
     */
    public static function shortenString($str, $length = 75, $break = '...') {
        if (strlen($str) < $length)
            return $str;

        $str = strip_tags($str);

        return substr($str, 0, $length) . $break;
    }

    /**
     * Uploads file(s) to the server
     * @param array $options Keys include [(string) path, (int) maxSize, (array) extensions]
     * @return boolean|string
     */
    public static function uploadFiles(array $options = array()) {
        foreach ($_FILES as $ppt => $info) {
            if ($info['error'] !== UPLOAD_ERR_OK)
                return self::UPLOAD_ERROR_NO_FILE;

            if (isset($options['maxSize']) && $info['size'] > $options['maxSize'])
                return self::UPLOAD_ERROR_SIZE;

            $tmpName = $info['tmp_name'];
            $info = pathinfo($info['name']);
            if (isset($options['extensions']) && !in_array($info['extension'], $options['extensions'])) {
                return self::UPLOAD_ERROR_EXTENSION;
            }
            $dir = isset($options['path']) ? $options['path'] : DATA . 'uploads';
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    return self::UPLOAD_ERROR_PATH;
                }

                $dir .= DIRECTORY_SEPARATOR . $info['extension'];
                if (!mkdir($dir, 0777, true)) {
                    return self::UPLOAD_ERROR_PERMISSION;
                }
            }

            $savePath = $dir . DIRECTORY_SEPARATOR . time() . '_' . preg_replace('/[^A-Z0-9._-]/i', '_', basename($info['filename'])) . '.' . $info['extension'];
            if (move_uploaded_file($tmpName, $savePath)) {
                self::$uploadSuccess = $savePath;
                return $savePath;
            }
            else {
                return self::UPLOAD_ERROR_FAILED;
            }
        }

        return self::UPLOAD_ERROR_NO_FILE;
    }

    /**
     * Generates a random password
     * @param int $length Length of the password to generate. Default is 8
     * @return string
     */
    public static function randomPassword($length = 8) {
        $chars = str_split(str_shuffle('bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ123456789.&^%$#@!)(\';:<>,"_-+='));
        $password = '';
        foreach (array_rand($chars, $length) as $key) {
            $password .= $chars[$key];
        }
        return $password;
    }

    public static function pathInfo($file) {
        return pathinfo(self::getFileName($file));
    }

    public static function getFileName($filePath) {
        return (stristr($filePath, '/')) ? substr($filePath, strripos($filePath, '/') + 1) : $filePath;
    }

}
