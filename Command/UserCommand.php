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
        $this->makeUsers($this->config['users']);

        return 0;
    }

    public function makeUsers(array $users): void
    {
        if (\count($users) === 0) {
            $this->io->note('No user to import');
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
            $user = $this->userService->getUser($resolved['username'], false);

            if ($user === null) {
                $user = new User();
            } elseif (!$this->forceUpdate('user', $resolved['username'])) {
                $this->io->note(\sprintf('User %s ignored', $resolved['username']));
                $this->io->progressAdvance();
                continue;
            }
            $user->setUsername($resolved['username']);
            $user->setDisplayName($resolved['display_name'] ?? $resolved['username']);
            $user->setPlainPassword($resolved['password']);
            $user->setEmail($resolved['email']);
            $user->setRoles($resolved['roles']);
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
            'username',
            'password',
            'email',
            'roles'
        ])->setDefaults([
            'display_name' => null,
            'wysiwyg_profile' => 'standard'
        ])
            ->setAllowedTypes('username', 'string')
            ->setAllowedTypes('display_name', ['string', 'null'])
            ->setAllowedTypes('password', 'string')
            ->setAllowedTypes('email', 'string')
            ->setAllowedTypes('roles', 'array')
            ->setAllowedTypes('wysiwyg_profile', 'string');

        return $resolver->resolve($user);
    }
}
