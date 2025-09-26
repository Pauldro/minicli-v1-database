<?php namespace Pauldro\Minicli\Database\Cmd;
use Exception;
// Pauldro Minicli
use Pauldro\Minicli\Cmd\AbstractController as ParentController;
use Pauldro\Minicli\Database\Credentials;
use Pauldro\Minicli\Database\CredentialsEnvParser;
use Pauldro\Minicli\Database\DatabaseConnector;
use Pauldro\Minicli\Services\Env;
use Pauldro\Minicli\Util\SessionVars;

/**
 * @property CredentialsEnvParser $dbCredentialsParser
 */
abstract class AbstractController extends ParentController {
    protected function init() : bool
    {
        if (parent::init() === false) {
            return false;
        }
        $this->dbCredentialsParser = new CredentialsEnvParser($this->app->dotenv);
        return true;
    }

    /**
     * Initialize Database Connection
     * @param  string $name                  Connection Name
     * @param  string $envPrefix             Prefix in .env file
     * @param  array  $credentialsOverrides  Credential overrides values
     * @return bool
     */
    protected function initDatabasex(string $name, string $envPrefix, array $credentialsOverrides = []) {
        try {
            $conf = $this->dbCredentialsParser->parse($envPrefix);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        foreach ($credentialsOverrides as $key => $value) {
            $conf->$key = $value;
        }

        $db = new DatabaseConnector($conf);

        if ($db->connect() === false) {
            $msg = $db->errorMsg ? $db->errorMsg : "Failed to connect to $name Database";
            return $this->error($msg);
        }
        SessionVars::setFor('databases', $name, $db);
        return true;
    }

    /**
     * Parse Database Credentials from .env
     * @param  string $prefix
     * @throws Exception
     * @return Credentials
     */
    protected function parseDatabaseCredentials(string $prefix) : Credentials
    {
        $suffixes = ['HOST', 'PORT', 'USER', 'PASSWORD', 'PROPEL.CONNECTION.NAME', 'PROPEL.ISDEFAULT'];
        $vars = [];

        foreach ($suffixes as $suffix) {
            $vars[] = "$prefix.$suffix";
        }

        /**  @var Env */
        $env = $this->app->dotenv;
        $env->required($vars);

        $conf = new Credentials();
        $conf->name = $env->get("$prefix.NAME");
        $conf->host = $env->get("$prefix.HOST");
        $conf->port = $env->get("$prefix.PORT");
        $conf->user = $env->get("$prefix.USER");
        $conf->password = $env->get("$prefix.PASSWORD");
        $conf->propelName = $env->get("$prefix.PROPEL.CONNECTION.NAME"); 
        $conf->isPropelDefault = $env->getBool("$prefix.PROPEL.ISDEFAULT");
        return $conf;
    }
}