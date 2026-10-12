<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Command;

use App\Domain\Core\Service\Encryption\SecretBoxCipher;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:integrations:encrypt-tokens',
    description: 'Encrypt plaintext integration tokens and re-encrypt tokens stored with a retired key',
)]
final class EncryptIntegrationTokensCommand extends Command
{
    private const TOKEN_COLUMNS = ['access_token', 'refresh_token'];

    public function __construct(
        private readonly Connection $connection,
        private readonly SecretBoxCipher $cipher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report how many rows would change without writing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');

        $integrationRows = $this->connection->fetchAllAssociative(
            'SELECT id, access_token, refresh_token FROM larp_integration ORDER BY id'
        );

        $changedIntegrations = 0;
        $changedTokens = 0;
        $this->connection->beginTransaction();
        try {
            foreach ($integrationRows as $integrationRow) {
                $encryptedTokens = $this->encryptedTokensFor($integrationRow);
                if ($encryptedTokens === []) {
                    continue;
                }

                $changedIntegrations++;
                $changedTokens += count($encryptedTokens);
                if ($isDryRun) {
                    continue;
                }

                $this->connection->update('larp_integration', $encryptedTokens, ['id' => $integrationRow['id']]);
            }
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        $io->success(sprintf(
            '%s %d token(s) across %d of %d integration(s).',
            $isDryRun ? 'Would encrypt' : 'Encrypted',
            $changedTokens,
            $changedIntegrations,
            count($integrationRows),
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $integrationRow
     * @return array<string, string>
     */
    private function encryptedTokensFor(array $integrationRow): array
    {
        $encryptedTokens = [];
        foreach (self::TOKEN_COLUMNS as $column) {
            $storedToken = $integrationRow[$column];
            if (!is_string($storedToken) || $this->cipher->isEncryptedWithCurrentKey($storedToken)) {
                continue;
            }

            $encryptedTokens[$column] = $this->cipher->reencrypt($storedToken);
        }

        return $encryptedTokens;
    }
}
