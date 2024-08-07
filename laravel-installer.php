<?php
$projectName = getProjectName();
installProject($projectName);
chdir($projectName);
installComponents();
installConfig();
applyPatches();
createConfigs();

/**
 * ================================================================================
 * Functions
 */


/**
 * Create necessary config files
 * @return void
 */
function createConfigs(): void
{
//TODO create README.md
    $php = '<?p'.'hp';
    echoLog("Create .php-cs-fixer.php");
    file_put_contents("./.php-cs-fixer.php", <<<EOF
$php

\$finder = PhpCsFixer\Finder::create()->exclude('storage')->in(__DIR__.'/app');

\$config = new PhpCsFixer\Config();

return \$config->setRules(
    [
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PHP80Migration:risky' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'blank_line_before_statement'=>[
            'statements' => ['break', 'case', 'continue', 'declare', 'default', 'exit', 'goto', 'include', 'include_once', 'phpdoc', 'require', 'require_once', 'return', 'switch', 'throw', 'try', 'yield', 'yield_from']
        ]
    ]
)->setFinder(\$finder);
EOF
);
    echoLog("Create phpstan.neon");
    file_put_contents("./phpstan.neon", <<<EOF
includes:
    - ./vendor/nunomaduro/larastan/extension.neon
parameters:
    treatPhpDocTypesAsCertain: false
    level: 5
    paths:
        - app
        - tests
EOF
);
    echoLog("Create Makefile");
    file_put_contents("./Makefile", <<<EOF
fix:
	./vendor/bin/php-cs-fixer --allow-risky=yes --config=.php-cs-fixer.php fix
test:
	./vendor/bin/phpstan analyse -c phpstan.neon
	./vendor/bin/phpunit tests
EOF
);
}

/**
 * Apply patches for configuration etc
 * @return void
 */
function applyPatches(): void
{
    $patches = [
        ["./config/models.php","'base_files' => false","'base_files' => true"]
    ];

    foreach($patches as $patch) {
        $result = patch($patch[0], $patch[1], $patch[2]);
        echoLog ("Patch {$patch[0]}" . (($result) ? " ... Success\n" : " ... Failed\n"), $result);

    }
}

/**
 * Get ProjectName from input parameters
 * @return string
 */
function getProjectName(): string
{
    if ($_SERVER["argc"] != 2) {
        echo "\n\nUsage: php ./laravel-installer.php <project_name>\n\n";
        exit(1);
    };
    return array_pop($_SERVER["argv"]);
}

/**
 * Install Laravel
 * @param string $projectName
 * @return void
 */
function installProject(string $projectName): void
{
    system("composer create-project laravel/laravel {$projectName} --prefer-dist");
}

/**
 * Install components
 * @return void
 */
function installComponents(): void
{
    $components = [
        "reliese/laravel" => "dev",
        "barryvdh/laravel-debugbar" => "dev",
        "friendsofphp/php-cs-fixer" => "dev",
        "nunomaduro/larastan" => "dev",
        "phpunit/phpunit" => "dev",
    ];

    foreach ($components as $component => $environment) {
        $environment = ($environment == "dev") ? "--dev" : "";
        system("composer require {$environment} {$component}");
    }
}

/**
 * Publish the configuration
 * @return void
 */
function installConfig(): void
{
    system("php artisan vendor:publish --tag=reliese-models");
}

function patch($filename, $search, $replace): bool
{
    $oldFile = file_get_contents($filename);
    $newFile = str_replace($search, $replace, $oldFile);
    file_put_contents($filename, $newFile);

    return $newFile!=$oldFile;
}

function echoLog($message, $success = true): void
{
    printf("[%s]\t{$message}\n", ($success) ? "SUCCESS" : "FAILURE");
}
