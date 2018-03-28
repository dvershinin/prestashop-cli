#!/usr/bin/env php
<?php

/*
 * Its simple class for resizing (all) (products) images in Prestashop
 * There is often a problem with resizing through dummy "Resize button" - PHP has timeout,
 * if not then gateway timeoout ...
 * So here it is, use it with PHP-CLI ( max_exec_time must be set to unlimited ... )
 */

// TODO Only products images
// TODO Regenerating all product images

// !!!! YOU MUST EDIT THIS
$PS_HOME        = dirname(__FILE__) . '/httpdocs/';    // directory wheres your PrestaShop installed
require_once $PS_HOME . "config/settings.inc.php";
$PS_IMG_PATH    = $PS_HOME . "img/p/";  // directory wheres your images

// DO NOT EDIT THIS BABY, UNLESS YOU KNOW WHAT ARE YOU DOING
require_once $PS_HOME . "config/config.inc.php";

class psThumbResizer {

    const version = 0.1;

    private $psImgDir;
    private $dbConn;
    private $tablePrefix;

    function __construct() {

    }

    function __destruct() {

    }

    /**
     * @param bool $afterDate - NOT IMPLEMENTED
     * @return array|bool - false if not success, array of IDs (strings) if success
     */
    private function getImageIds( $afterDate = false) {
        if( !$afterDate ) {
            $sql = "SELECT `id_image` FROM `" . $this->tablePrefix . "image`";
            $result = $this->dbConn->query($sql);

            if ( ( is_object( $result ) ) && ( $result->num_rows > 0 ) ) {
                while($row = $result->fetch_row()) {
                    $rows[] = $row[0];
                }
                return $rows;
            }
            return false;
        }
    }

    private function createPathToImage( $imageId ) {
        $chars = str_split( $imageId );
        foreach($chars as $char){
            $ret[] = '/' . $char;
        }
        return implode( $ret );
    }

    /**
     * @param $psImgDir - relative path to your PS_INSTALL_DIR/img/p
     */
    public function setPsImgDir( $psImgDir ) {
        if ( !is_dir( $psImgDir ) ) {
            die( $psImgDir . " is not a valid directory" );
        }
        $this->psImgDir = realpath( $psImgDir );
    }

    public function regenerateImages()  {
        // get Ids
        $arrImgIds = $this->getImageIds();
        $realImgPath = realpath($this->psImgDir);

        foreach ( $arrImgIds as $imagePath ) {
            $imgPath = $realImgPath . $this->createPathToImage( $imagePath ) . '/' . str_replace('/','', $imagePath) . ".jpg";
            $types = ImageType::getImagesTypes( "products" );
            if ( file_exists( $imgPath ) && filesize( $imgPath ) )
            {
                foreach ($types as $imageType)
                    if (!ImageManager::resize( $imgPath, substr($imgPath, 0, strrpos( $imgPath, '.' ) ) . '-'
                            . stripslashes($imageType[ 'name' ] ) . '.jpg' ,
                            ( int ) ( $imageType[ 'width' ] ),
                            ( int ) ( $imageType[ 'height' ] ) ) )
                    {
                        echo "ERROR: Original image " . $imagePath . " is corrupt or has bad permission on folder" . PHP_EOL;
                    }
                    else {
                        echo "Regenerated image: " . substr($imgPath, 0, strrpos( $imgPath, '.' ) )
                                                   . '-' . stripslashes($imageType[ 'name' ] ) . '.jpg' . PHP_EOL;
                    }
            }
            else {
                echo "ERROR: Original image: " . $imgPath . " does not exists" . PHP_EOL;
            }

        }
    }
    public function dbConnect( $dbUser, $dbPass, $dbHost, $dbName, $tablePrefix ) {
        $this->dbConn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        // Check connection
        if ($this->dbConn->connect_error) {
            die( "Connection to database failed: " . $this->dbConn->connect_error );
        }

        $this->tablePrefix = $tablePrefix;
        $this->dbConn->set_charset('utf8');
    }
}

$pstr = new psThumbResizer();
$pstr->setPsImgDir( $PS_IMG_PATH );
$pstr->dbConnect(_DB_USER_, _DB_PASSWD_, _DB_SERVER_, _DB_NAME_, _DB_PREFIX_);
$pstr->regenerateImages();
