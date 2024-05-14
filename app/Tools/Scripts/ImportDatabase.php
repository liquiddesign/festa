<?php

namespace App\Tools\Scripts;

use App\Tools\Script;
use Lqd\Security\Authenticator;
use Nette\Utils\Finder;
use Storm\Connection;
use Storm\Migrator;

class ImportDatabase extends Script
{
    /**
     * @var \Storm\Connection
     */
    private $stm;

    /**
     * @var \Storm\Migrator
     */
    private $migrator;

    private const SQLDIR = '_sql';

    public function __construct(Connection $stm, Migrator $migrator)
    {
        $this->stm = $stm;
        $this->migrator = $migrator;
    }

    /**
     * Import from url
     *
     * @param string $url
     * @throws \Nette\Application\ApplicationException
     */
    public function remoteImport(string $url): void
    {
        $password = $this->getIO()->ask('Zadejte LQD heslo:', '');

        $this->write('Připojuji se na URL '. $url);

        $opts = ['http' => [
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => \http_build_query(['password' => Authenticator::setCredentialTreatment($password)]),
            ],
        ];

        $context  = \stream_context_create($opts);
        $sql = \file_get_contents($url, false, $context);

        if (!$this->migrator->isDatabaseEmpty()) {
            if (!$this->getIO()->askConfirmation('Pozor vaše databáze není prázdná! Importem dojde k přemazání současných dat. Přejete si pokračovat? (y)')) {
                $this->write("Import databáze byl zrušen");

                return;
            }
        }

        $this->write('Nahrávám databázi ... ');
        $this->stm->query($sql);
        $this->write('hotovo.');
    }

    /**
     * Import local file
     *
     * @param string $filename
     * @throws \Nette\Application\ApplicationException
     * return void
     */
    public function localImport(string $filename): void
    {
        $this->write("Nahrávám databázi ze souboru $filename ... ");
        $this->migrator->databaseImport($this->getBaseDir() . '/' . $filename);
        $this->write('hotovo.');
    }

    /**
     * Do import
     *
     * @param string|null $argument
     * @throws \Nette\Application\ApplicationException return void
     */
    public function doImportDatabase(?string $argument = null): void
    {
        $args = $argument ? [$argument] : $this->getArguments();

        if (isset($args[0]) && \filter_var(\gethostbyname($args[0]), \FILTER_VALIDATE_IP)) {
            $url = $args[0];
            $this->remoteImport("http:\\$url\scripts\dump-database");
        } elseif (isset($args[0])) {
            $this->localImport(self::SQLDIR . '/' .$args[0]);
        } else {
            $i = 0;
            $options = [];

            foreach (Finder::findFiles('*.sql')->in($this->getBaseDir() . '/_sql') as $file) {
                $options[$i] = $file->getFileName();
                $i += 1;
            }

            $result = $this->getIO()->select('Vyberte soubor:', $options, 0);

            $this->localImport(self::SQLDIR . '/' . $options[$result]);
        }
    }
}
