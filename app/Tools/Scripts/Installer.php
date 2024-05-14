<?php

namespace App\Tools\Scripts;

use App\Tools\Script;
use Lqd\Userfiles\Userfiles;
use Nette\Neon\Neon;
use Storm\Connection;
use Storm\Migrator;

class Installer extends Script
{
    /**
     * @var \Storm\Connection
     */
    private $stm;

    /**
     * @var \Storm\Migrator
     */
    private $migrator;

    /**
     * @var \Lqd\Userfiles\Userfiles
     */
    private $userfiles;

    /**
     * @var string
     */
    private $projectName = '';

    /**
     * @var string[]
     */
    private $config = [];

    /**
     * @var string[]
     */
    private $localConfig = [];

    private const CONFIGDIR = 'app/config';

    public function __construct(Userfiles $userfiles)
    {
        $this->userfiles = $userfiles;
    }

    /**
     * Login to database
     *
     * @throws \Nette\Application\ApplicationException
     * return void
     */
    public function doDatabaseLogin(): void
    {
        $this->projectName = $dbName = \basename($this->getBaseDir());

        $this->write('Liquid Design s.r.o. - Defaultweb3');
        $this->write('(v závorce konfigurací, uvidíte defaultní hodnotu - stačí dát jen enter)');

        $this->config = [
            'parameters' => [
                'langs' => [],
            ],
            'includes' => [
                'pages.neon',
            ],
            'modules' => [],
        ];

        $user = $this->getIO()->ask("Zadejte uživatele databáze ('root'):", 'root');
        $password = $this->getIO()->ask("Zadejte heslo databáze (''):", '');
        $this->write("Zvolený název databáze dle adresáře: $dbName");
        $host = 'localhost';

        try {
            $this->stm = new Connection("mysql:host=$host", $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $x) {
            $this->writeError('Nepodařilo se připojit k databázi.');
            $this->terminate();

            return;
        }

        $this->localConfig = [
            'storm' => [
                'default' => [
                    'host' => $host,
                    'user' => $user,
                    'password' => $password,
                    'dbname' => $this->projectName,
                ],
            ],
        ];
    }

    /**
     * Create config
     *
     * @throws \Nette\Application\ApplicationException
     * return void
     */
    public function doCrateConfig(): void
    {
        $config = 'config.custom.neon';

        if (\is_file($this->getBaseDir() . '/' . self::CONFIGDIR . '/' . $config)) {
            $this->write("Config $config již existuje. Přeskakuji konfiguraci.");

            return;
        }

        if ($this->getIO()->askConfirmation('Bude web vícejazyčný? (n)', false)) {
            $answer = $this->getIO()->ask('Napište jazyky (dvoupísmené) oddělené čárkou (cz,en):', 'cz,en');
            $this->config['parameters'] = ['langs' => \explode(',', $answer),];
        } else {
            $this->config['parameters'] = ['langs' => \explode(',', 'cz'),];
        }

        $this->config['includes'][] = '../../vendor/lqdlib/translator/config.neon';

        $debugPassword = $this->getIO()->ask("Zadejte heslo pro cookie nette-debug (''):", '');

        if ($debugPassword) {
            $this->config['parameters']['lqd_ip'] = $debugPassword . '@' . $this->config['parameters']['lqd_ip'];
        }

        if ($this->getIO()->askConfirmation("Povolit modul 'eshop'? (n)", false)) {
            $this->config['includes'][] = '../../vendor/lqdlib/eshop/config.neon';
            $this->config['modules'][] = 'eshop';
        }

        \file_put_contents($this->getBaseDir() . '/' . self::CONFIGDIR . '/' . $config, Neon::encode($this->config, Neon::BLOCK), Neon::BLOCK);
    }

    /**
     * Create config
     *
     * @throws \Nette\Application\ApplicationException
     * return void
     */
    public function doCrateLocalConfig(): void
    {
        $config = 'config.local.neon';

        if (\is_file($this->getBaseDir() . '/' . self::CONFIGDIR . '/' . $config)) {
            $this->write("Config $config již existuje. Přeskakuji konfiguraci.");

            return;
        }

        \file_put_contents($this->getBaseDir() . '/' . self::CONFIGDIR . '/' . $config, Neon::encode($this->localConfig, Neon::BLOCK), Neon::BLOCK);
    }

    /**
     * Create database
     *
     * @throws \Nette\Application\ApplicationException
     * return void
     */
    public function doCreateDatabase(): void
    {
        $dbName = $this->projectName;

        try {
            $this->stm->useDatabase($dbName);
        } catch (\PDOException $x) {
            $this->stm->createDatabase($dbName);
            $this->stm->useDatabase($dbName);
        }

        if (!$this->stm->isDatabaseEmpty()) {
            $this->write("Databáze $dbName není prázdná. Přeskakuji import dat.");

            return;
        }

        $this->migrator = $this->getContainer()->getByType(Migrator::class);
        $this->migrator->setStm($this->stm);

        $answer = $this->getIO()->select('Zvolte způsob nahrání databáze: (0)', [
            0 => 'Vytvořit pouze strukturu databáze',
            1 => 'Import ze souboru nebo url',
        ], 0);
        
        if ($answer === '0') {
            $this->write('Vytvářím strukturu databáze');
            $this->stm->query($this->migrator->getSyncString($this->getContainer()->parameters['composer']->getPrefixesPsr4()));
            $this->write('Struktura vytvořena');
        } else {
            $argument = $this->getIO()->ask('Zajdete soubor k importu nebo doménu pro SQL dump (prázdné=vypíše seznam)');
            $this->getScript(ImportDatabase::class, [$argument])->doImportDatabase();
        }

        // import data
        $this->getScript(SyncData::class, [])->doSyncData();
    }

    public function doCreateDirectories(): void
    {
        $this->userfiles->createDirectories($this->getBaseDir());
    }

    public function doCopyGithook(): void
    {
        \copy($this->getBaseDir() . '/temp/installer/pre-commit', $this->getBaseDir() . '/.git/hooks/pre-commit');
    }
}
