<?php
namespace PHPQueue\Interfaces;
interface Config
{
    /**
     * @param string $type
     * @return array
     */
    static public function getConfig($type=null);

    /**
     * @return string
     */
    static public function getAppRoot();

    /**
     * @return string No trailing slash
     */
    static public function getLogRoot();
} 