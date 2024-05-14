<?php

namespace App\Tools;

use Nette\Application\ApplicationException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Presenter;
use Nette\Utils\Strings;

/**
 * Scripts Presenter
 */
class RunPresenter extends Presenter
{
    /**
     * Run script
     *
     * @param null|string $namespace
     * @param null|string $script
     * @throws \Nette\Application\ApplicationException
     * @throws \Nette\Application\ForbiddenRequestException
     * @throws \Nette\Application\AbortException
     */
    public function actionDefault(?string $namespace = null, ?string $script = null): void
    {
        if (!$this->context->parameters['debugMode']) {
            throw new ForbiddenRequestException('Turn on debug mode');
        }

        if ($script === null) {
            $script = $namespace;
            $namespace = null;
        }

        $scriptName = Strings::firstUpper(\preg_replace_callback("/-[a-zA-Z]/", static function ($matches) {
            return Strings::upper($matches[0][1]);
        }, $script));

        $namespaceName = $namespace ? Strings::firstUpper($namespace) : null;

        $class = $namespaceName ? "\\App\\$namespaceName\\Scripts\\$scriptName" : "\\App\\Tools\\Scripts\\$scriptName";

        if (!\class_exists($class)) {
            throw new ApplicationException("Scripts class not found: $class");
        }

        $class::run($this->context, $this->getHttpRequest()->getQuery());

        $this->terminate();
    }
}
