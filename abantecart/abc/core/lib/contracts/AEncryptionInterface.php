<?php

namespace abc\core\lib\contracts;

interface AEncryptionInterface
{
    /**
     * AEncryption constructor.
     *
     * @param string $key
     */
    function __construct(string $key);

    /**
     * Encode function
     *
     * @param string $str
     *
     * @return string
     */
    function encrypt(string $str);

    /**
     * @param string $dbDriver
     * @param string $tableAlias
     * @param string $password
     *
     * @return string
     */
    public function getRawSqlHash(string $dbDriver, string $tableAlias, string $password);

    /**
     * Decode function
     *
     * @param string $enc_str
     *
     * @return string
     */
    function decrypt(string $enc_str);

    /**
     * @param string $keyword
     * @param string $salt_key
     *
     * @return string
     */
    static function getHash(string $keyword, string $salt_key);
}