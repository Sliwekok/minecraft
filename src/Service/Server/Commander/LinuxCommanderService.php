<?php

declare(strict_types=1);

namespace App\Service\Server\Commander;

use App\Entity\Server;
use App\Exception\Server\ServerIsAlreadyRunningException;
use App\Service\Filesystem\FilesystemService;
use App\Service\Helper\RunCommandHelper;
use App\UniqueNameInterface\ServerUnixCommandsInterface;
use App\UniqueNameInterface\ServerWindowsCommandsInterface;

class LinuxCommanderService
{

    public function __construct (
        private RunCommandHelper    $commandHelper,
        private UnixSessionService  $unixSessionService
    ) {}

    /**
     * creates separate screen that holds session
     */
    public function startServer (
        Server $server
    ): void {
        $path = (new FilesystemService($server->getDirectoryPath()))->getAbsoluteMinecraftPath();
        $screenExists = $this->unixSessionService->checkScreenExists($server);
        if ($screenExists) {
            // show error to user about server that is already running
            throw new ServerIsAlreadyRunningException();
        }

        // create new session
        UnixSessionService::createNewSession($server);
        // run server
        $command = $this->getStartupCommand($server);
        $this->commandHelper->runCommand($command, $path);
    }

    /**
     * end process server via CommandLine
     */
    public function stopServer (
        Server $server
    ): void {
        UnixSessionService::attachToSession($server);
        $command = $this->getStopCommand($server);

        $this->commandHelper->runCommand($command);
    }

    /**
     * create server booting command to CLI
     * creates new screen with commands
     */
    private function getStartupCommand (
        Server  $server,
    ): array {
        $ram = $server->getConfig()->getMaxRam();
        $java = ServerUnixCommandsInterface::RUN_SERVER;
        $java = str_replace(ServerUnixCommandsInterface::REPLACEMENT_RAM, (string)$ram, $java);

        $screen = ServerUnixCommandsInterface::SCREEN_CREATE;
        $screen = str_replace(ServerUnixCommandsInterface::REPLACEMENT_NAME, (string)$server->getName(), $screen);

        return [$screen, $java];
    }

    /**
     * create server killing command to CLI
     * creates new screen named after server name and there are all commands inserted
     */
    private function getStopCommand (
        Server $server
    ): string {
        $command = str_replace(ServerWindowsCommandsInterface::REPLACEMENT_NAME, (string)$server->getName(), ServerWindowsCommandsInterface::STOP_JAVA);

        return $command;
    }

}