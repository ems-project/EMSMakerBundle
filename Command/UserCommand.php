<?php

declare(strict_types=1);

namespace EMS\MakerBundle\Command;

use EMS\CoreBundle\Entity\User;
use EMS\CoreBundle\Service\UserService;
use EMS\CoreBundle\Service\WysiwygProfileService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserCommand extends AbstractCommand
{
    private const USERNAME = 'username';
    private const DISPLAY_NAME = 'display_name';
    private const PASSWORD = 'password';
    private const EMAIL = 'email';
    private const ROLES = 'roles';
    private const WYSIWYG_PROFILE = 'wysiwyg_profile';
    /** @var string  */
    protected static $defaultName = 'ems:maker:user';
    private UserService $userService;
    private WysiwygProfileService $wysiwygProfileService;

    public function __construct(UserService $userService, WysiwygProfileService $wysiwygProfileService)
    {
        parent::__construct();
        $this->userService = $userService;
        $this->wysiwygProfileService = $wysiwygProfileService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->makeUsers($this->config[AbstractCommand::USERS]);

        return 0;
    }

    public function makeUsers(array $users): void
    {
        if (\count($users) === 0) {
            $this->io->note('No user to import');

            return;
        }

        $profile = null;
        foreach ($this->wysiwygProfileService->getProfiles() as $item) {
            $profile = $item;
            break;
        }


        $this->io->title('Make users');
        $this->io->progressStart(\count($users));
        foreach ($users as $user) {
            $resolved = $this->resolveUser($user);
            $user = $this->userService->getUser($resolved[self::USERNAME], false);

            if ($user === null) {
                $user = new User();
            } elseif (!$this->forceUpdate('user', $resolved[self::USERNAME])) {
                $this->io->note(\sprintf('User %s ignored', $resolved[self::USERNAME]));
                $this->io->progressAdvance();
                continue;
            }
            $user->setUsername($resolved[self::USERNAME]);
            $user->setDisplayName($resolved[self::DISPLAY_NAME] ?? $resolved[self::USERNAME]);
            $user->setPlainPassword($resolved[self::PASSWORD]);
            $user->setEmail($resolved[self::EMAIL]);
            $user->setRoles($resolved[self::ROLES]);
            $user->setEnabled(true);
            $user->setWysiwygProfile($profile);
            $this->userService->updateUser($user);

            $this->io->progressAdvance();
        }
        $this->io->progressFinish();
    }

    private function resolveUser(array $user)
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([
            self::USERNAME,
            self::PASSWORD,
            self::EMAIL,
            self::ROLES,
        ])->setDefaults([
            self::DISPLAY_NAME => null,
            self::WYSIWYG_PROFILE => 'standard',
        ])
            ->setAllowedTypes(self::USERNAME, 'string')
            ->setAllowedTypes(self::DISPLAY_NAME, ['string', 'null'])
            ->setAllowedTypes(self::PASSWORD, 'string')
            ->setAllowedTypes(self::EMAIL, 'string')
            ->setAllowedTypes(self::ROLES, 'array')
            ->setAllowedTypes(self::WYSIWYG_PROFILE, 'string');

        return $resolver->resolve($user);
    }
}
