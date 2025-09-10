<?php namespace Pauldro\Minicli\Database;
// Pauldro Minicli
use Pauldro\Minicli\Util\Data;

/**
 * DatabaseConnector
 * Makes Database Connection
 *
 * @property string $name     Database Name
 * @property string $host     Database Host Address
 * @property int    $port     Database Port
 * @property string $user     Database User
 * @property string $password Database User's Password
 * @property string $propelName Propel Connection Name
 * @property bool   $isPropelDefault
 */
class Credentials extends Data {
    public function __construct() {
        $this->name = '';
        $this->host = 'localhost';
        $this->port = 3306;
        $this->user = '';
        $this->password = '';
        $this->propelName = '';
        $this->isPropelDefault = false;
    }
}
