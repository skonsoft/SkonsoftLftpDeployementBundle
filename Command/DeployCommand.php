<?php

namespace Skonsoft\Bundle\LftpDeployementBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;

/**
 * Description of DeployCommand
 *
 * @author skander mabrouk <mabroukskander@gmail.com>
 */
class DeployCommand extends ContainerAwareCommand
{

    protected $defaultLftpOptions = 'set ftp:use-allo no; set ftp:use-feat no; set ftp:ssl-allow no; set ftp:list-options -a;';

    protected function configure()
    {
        $this
                ->setName('skonsoft:deploy')
                ->setDescription('Deploy the project using LFTP')
                ->addArgument('server', InputArgument::REQUIRED, 'Where you want to deploy your project')
                ->addOption('go', null, InputOption::VALUE_NONE, 'If set, the task will deploy the project')
                ->addOption('show-config', null, InputOption::VALUE_NONE, 'If set, task will show your deployment cnfiguration')
                ->addOption('lftp-commands', null, InputOption::VALUE_OPTIONAL, 'If set, it replaces the default LFTP commands: ')
                ->setHelp(<<<EOT
The <info>skonsoft:deploy</info> command helps you to deploy your sources in your web server using LFTP.
By default, this command executes LFTP with your config information set under app/config/config.yml


<comment>Usage:</comment>
<info>app/console skonsoft:deploy server [--go] [--show-config] [--lftp-commands] [--verbose]</info>

<comment>Example:</comment>

<info>./app/console skonsoft:deploy prod --go</info>
this command will execute the deployment quietly

<info>./app/console skonsoft:deploy prod --go  --verbose </info>
this command will execute the deployment in verbose mode

<info>./app/console skonsoft:deploy prod --show-config </info>
shows your prod server's config. these config should be set on app/config/config.yml

<info>./app/console skonsoft:deploy prod --lftp-commands="{$this->defaultLftpOptions}" </info>
    this command will execute the LFTP client with your own lftp options. the default options will be replaced by your own.

EOT
        );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        try {

            $config = $this->getConfig($input->getArgument('server'));

            //if just showing config, we show them and we exit
            if ($input->getOption('show-config')) {
                $this->showConfig($config, $output);
                return 1;
            }

            if ($input->isInteractive()) {
                $output->writeln(array(
                    '',
                    sprintf('server: %s', $input->getArgument('server')),
                    sprintf('hostname: %s', $config['hostname'])
                ));
                if (empty($config['login'])) {
                    $config['login'] = $dialog->ask($output, 'Login: ');
                } else {
                    $output->writeln('login: ' . $config['login']);
                }

                //$config['password'] = $dialog->ask($output, 'Password: ');
            }

            $config['local_dir'] = realpath($this->getContainer()->get('kernel')->getRootDir() . '/../'); //emplacement local

            $config['lftp-commands'] = $this->defaultLftpOptions;

            if ($input->getOption('lftp-commands')) {
                $config['lftp-commands'] = $input->getOption('lftp-commands');
            }

            $config['verbose'] = '';

            if ($input->getOption('verbose')) {
                $config['verbose'] = '--verbose';
            }

            if (!$input->getOption('go')) {
                if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm deployment', 'yes', '?'), true)) {
                    $output->writeln('<error>Command aborted</error>');

                    return 1;
                }
            }

            $ignored_dirs = '';

            if (isset($config['exclude_file'])) {
                try {
                    $ignored_dirs = $this->getExcludeLftpString($config['exclude_file']);
                } catch (\Exception $e) {
                    $output->writeln(array(
                        '',
                        '<comment>Some errors was occured when trying to get the ignored files/directories.</comment>',
                        'Error message:',
                       $e->getMessage(),
                        ''
                        ));
                    if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you want continue deployment', 'yes', '?'), true)) {
                        $output->writeln('<error>Command aborted</error>');

                        return 1;
                    }
                }
            }

            $ftpcommand = sprintf('%s open ftp://%s@%s:%s; lcd %s; cd %s; mirror --reverse --delete %s %s ; quit',
                    $config['lftp-commands'],
                    $config['login'],
                    //$config['password'],
                    $config['hostname'],
                    $config['port'],
                    $config['local_dir'],
                    $config['path'],
                    $config['verbose'],
                    $ignored_dirs
            );

            $command = sprintf('lftp -c "%s"', $ftpcommand);
            $output->writeln(array(
                '',
                '<info>Executing LFTP command:</info>',
                $command,
                ''
            ));

            $exec = system($command, $status);
        } catch (\Exception $e) {

            $output->writeln($e->getMessage());
        }
    }

    protected function showConfig($config, OutputInterface $output)
    {
        $output->writeln(array(
            '',
            '<comment>Your Config set on app/config/config.yml:</comment>',
            sprintf('<info>%s</info>:%s', 'host', $config['hostname']),
            sprintf('<info>%s</info>:%s', 'Remote Path', isset($config['path']) ? $config['path'] : '/'),
            sprintf('<info>%s</info>:%s', 'port', isset($config['port']) ? $config['port'] : '21'),
            sprintf('<info>%s</info>:%s', 'login', isset($config['login']) ? $config['login'] : ''),
            sprintf('<info>%s</info>:%s', 'Exclude File', isset($config['exclude_file']) ? $config['exclude_file'] : ''),
            ''
        ));
    }

    protected function getConfig($server)
    {
        try {
            $skonsoft = $this->getContainer()->getParameter('skonsoft');
            if (!isset($skonsoft['lftp_deployement']))
                throw new \Exception('The key lftp_deployement is not set under skonsoft parameter. ');

            if (!isset($skonsoft['lftp_deployement'][$server]))
                throw new \Exception(sprintf('the configuration for server %s is not set under lftp_deployement', $server));

            $server_key = $skonsoft['lftp_deployement'][$server];

            if (!isset($server_key['hostname']))
                throw new \Exception(sprintf('the hostname for server %s is not set under lftp_deployement', $server));
            $conf = array();
            $conf['hostname'] = $server_key['hostname'];
            $conf['login'] = isset($server_key['login']) ? $server_key['login'] : null;
            $conf['port'] = isset($server_key['port']) ? $server_key['port'] : '21';
            $conf['exclude_file'] = isset($server_key['exclude_file']) ? $server_key['exclude_file'] : null;
            $conf['path'] = isset($server_key['path']) ? $server_key['path'] : '/';
            return $conf;
        } catch (\Exception $e) {
            throw new \Exception('Invalid config parameters in config.yml: ' . $e->getMessage());
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $dialog->writeSection($output, 'Welcome to the Symfony2 LFTP Deployement Bundle');
        $output->writeln(array(
            '',
            'this command helps you to deploy your project under web server using LFTP.',
            ''
        ));
    }

    protected function getDialogHelper()
    {
        $dialog = $this->getHelperSet()->get('dialog');
        if (!$dialog || get_class($dialog) !== 'Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper') {
            $this->getHelperSet()->set($dialog = new DialogHelper());
        }

        return $dialog;
    }

    protected function getExcludeLftpString($filename)
    {
        if (!file_exists($filename))
            throw new \Exception('the exclude file not found: ' . $filename);
        if (!is_readable($filename))
            throw new \Exception('the exclude file is not readable: ' . $filename);
        $toignore = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $ignored = array();

        foreach ($toignore as $ressource) {
            if (!strncmp($ressource, '#', 1)) //lines to be ignored if starts with #
                continue;

            if (!strncmp($ressource, '*', 1)) { // if starts with *, global exclude
                $ignored[] = sprintf('--exclude-glob %s', $ressource);
            } else {

                $ignored[] = sprintf('--exclude %s', $ressource);
            }
        }
        return implode(' ', $ignored);
    }

}

?>
