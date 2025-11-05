<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:github:get-category-id',
    description: 'Get GitHub Discussion Category IDs for the feedback system',
)]
class GetGithubCategoryIdCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $githubToken,
        private readonly string $githubRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('GitHub Discussion Categories Finder');

        // Check if token is configured
        if (empty($this->githubToken) || $this->githubToken === 'your-github-token-here') {
            $io->error('GitHub token not configured. Please set GITHUB_TOKEN in your .env.local file.');
            return Command::FAILURE;
        }

        // Parse repo
        [$owner, $repo] = explode('/', $this->githubRepo);

        $io->info("Repository: {$this->githubRepo}");
        $io->newLine();

        // GraphQL query
        $query = <<<GRAPHQL
query {
  repository(owner: "{$owner}", name: "{$repo}") {
    discussionCategories(first: 20) {
      nodes {
        id
        name
        slug
        description
        isAnswerable
      }
    }
  }
}
GRAPHQL;

        try {
            $response = $this->httpClient->request('POST', 'https://api.github.com/graphql', [
                'headers' => [
                    'Authorization' => "Bearer {$this->githubToken}",
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'LARPilot-Feedback-System',
                ],
                'json' => [
                    'query' => $query,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['errors'])) {
                $io->error('GraphQL Error:');
                $io->listing(array_map(fn($e) => $e['message'], $data['errors']));

                if (str_contains(json_encode($data['errors']), 'discussions')) {
                    $io->warning([
                        'It looks like Discussions are not enabled for this repository.',
                        "Please enable them at: https://github.com/{$this->githubRepo}/settings",
                        'Scroll to Features and check the "Discussions" checkbox.',
                    ]);
                }

                return Command::FAILURE;
            }

            $categories = $data['data']['repository']['discussionCategories']['nodes'] ?? [];

            if (empty($categories)) {
                $io->warning([
                    'No discussion categories found.',
                    "Please enable Discussions at: https://github.com/{$this->githubRepo}/settings",
                ]);
                return Command::FAILURE;
            }

            $io->success(sprintf('Found %d discussion categories:', count($categories)));
            $io->newLine();

            $tableRows = [];
            foreach ($categories as $category) {
                $tableRows[] = [
                    $category['name'],
                    $category['slug'],
                    $category['id'],
                    $category['isAnswerable'] ? 'Yes' : 'No',
                    $category['description'] ?? '-',
                ];
            }

            $io->table(
                ['Name', 'Slug', 'Category ID', 'Q&A?', 'Description'],
                $tableRows
            );

            $io->newLine();
            $io->section('Recommended Setup');

            // Find recommended category
            $recommendedCategory = null;
            foreach ($categories as $category) {
                if (in_array(strtolower($category['name']), ['general', 'feedback'])) {
                    $recommendedCategory = $category;
                    break;
                }
            }

            if (!$recommendedCategory) {
                $recommendedCategory = $categories[0];
            }

            $io->text([
                'For the LARPilot feedback system, add this to your .env.local:',
                '',
                "<info>GITHUB_DISCUSSION_CATEGORY_ID={$recommendedCategory['id']}</info>",
                '',
                "This will use the \"{$recommendedCategory['name']}\" category for questions and general feedback.",
                'Bug reports and feature requests will still go to Issues.',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error([
                'Failed to fetch discussion categories:',
                $e->getMessage(),
            ]);

            if (str_contains($e->getMessage(), 'authentication')) {
                $io->warning('Check that your GitHub token is valid and has the correct scopes (repo, write:discussion).');
            }

            return Command::FAILURE;
        }
    }
}
