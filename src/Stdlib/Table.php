<?php

/**
 * Class Table
 *
 * @author Ezra Obiwale
 */
class Table {

    private static $table_attributes;
    private static $rows;
    private static $row_data;
    private static $headers;
    private static $footers;

    /**
     * Sets up a new table
     * @param array $attributes [optional] Array of attr => value
     */
    public static function init(array $attributes = array()) {
        self::$table_attributes = array();
        self::$rows = array();
        self::$row_data = array();
        self::$headers = array();

        self::setAttributes($attributes);
    }

    /**
     * Sets attributes for the table
     * @param array $attributes Array of attr => value
     */
    public static function setAttributes(array $attributes) {
        self::$table_attributes = $attributes;
    }

    /**
     * Sets header data
     * @param array $data Array of labels for each column
     * @param array $attributes Array of attr => value to apply to each column
     */
    public static function setHeaders(array $data, array $attributes = array()) {
        foreach ($data as $header) {
            self::addHeader($header, $attributes);
        }
    }

    /**
     * Adds individual column data to header
     * @param string $data Column data
     * @param array $attributes Array of attr => value to apply to the column
     */
    public static function addHeader($data = '', array $attributes = array()) {
        self::$headers[] = array($data, $attributes);
    }

    /**
     * Adds column data to the current row
     * @param string $data Column data
     * @param array $attributes Array of attr => value to apply to the column
     */
    public static function addRowData($data = '', array $attributes = array()) {
        self::$row_data[] = array($data, $attributes);
    }

    /**
     * Adds individual column data to header
     * @param string $data Column data
     * @param array $attributes Array of attr => value to apply to the column
     */
    public static function addFooter($data = '', array $attributes = array()) {
        self::$footers[] = array($data, $attributes);
    }

    /**
     * Signals beginning of a new row
     * @param array $attrs Array of attr => value to apply to the row
     */
    public static function newRow(array $attrs = array()) {
        if (!empty(self::$row_data))
            self::$rows[count(self::$rows) - 1][] = self::$row_data;
        self::$row_data = array();
        self::$rows[][] = $attrs;
        return count(self::$rows);
    }

    private static function parseAttributes(array $attrs) {
        $return = '';
        foreach ($attrs as $attr => $value) {
            $return .= $attr . '= "' . addslashes($value) . '" ';
        }
        return $return;
    }

    /**
     * Renders the HTML Table
     * @return HTMLContent
     */
    public static function render() {
        self::newRow();
        ob_start();
        ?>

        <table <?php echo (!empty(self::$table_attributes)) ? self::parseAttributes(self::$table_attributes) : ""; ?>>
            <?php
            if (!empty(self::$headers)) {
                ?>

                <thead>
                    <tr>
                        <?php
                        foreach (self::$headers as $header) {
                            ?>

                            <th <?php echo self::parseAttributes($header[1]); ?>><?php echo $header[0] ?></th>
                            <?php
                        }
                        ?>
                    </tr>
                </thead>
                <?php
            }
            if (!empty(self::$rows)) {
                ?>
                <tbody>
                    <?php
                    foreach (self::$rows as $rowData) {
                        if (count($rowData) > 1) {
                            ?>
                            <tr <?php echo self::parseAttributes($rowData[0]); ?>>
                                <?php
                                foreach ($rowData[1] as $row) {
                                    ?>
                                    <td <?php echo self::parseAttributes($row[1]); ?>><?php echo $row[0] ?></td>
                                    <?php
                                }
                                ?>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
                <?php
            }
            if (!empty(self::$footers)) {
                ?>
                <tfoot>
                    <tr>
                        <?php
                        foreach (self::$footers as $footer) {
                            ?>

                            <th <?php echo self::parseAttributes($footer[1]); ?>><?php echo $footer[0] ?></th>
                            <?php
                        }
                        ?>
                    </tr>
                </tfoot>
            <?php }
            ?>
        </table>
        <?php
        return ob_get_clean();
    }

}
