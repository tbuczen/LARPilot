# Integration test fixtures

Recorded-shape API payloads so integration code can be tested without touching
the network. Every file is a raw response **body** exactly as the provider
returns it; status codes and headers are attached when the route is
registered (see below).

| Directory  | API                 | Files |
|------------|---------------------|-------|
| `discord/` | Discord API v10     | `guild.json`, `channels.json` (category, text, voice and a forum with `available_tags`), `threads_active.json`, `threads_archived_public.json` (`has_more: true`), `messages.json` (newest first, incl. a reply, mentions, an attachment and a forum starter message whose id equals the thread id), `rate_limited.json` (429), `missing_access.json` (403, code `50001`) |
| `miro/`    | Miro REST API v2    | `boards.json`, `board_items_page_1.json` (has `cursor`), `board_items_page_2.json` (last page, no `cursor`), `frame_children.json`, `connectors.json` (with `captions`), `tags.json`, `rate_limited.json` (429), `unauthorized.json` (401) |
| `google/`  | Google Sheets API v4 | `sheet_values_characters.json` (`spreadsheets.values.get`, ragged rows) |

IDs used across files are consistent (the forum's `available_tags` ids are the
threads' `applied_tags`; the frame's children have `parent.id` set to the
frame; connectors point at item ids from the items pages). The most useful ids
are exposed as constants on `Tests\Support\Http\IntegrationFixtureRouters`.

## Mock HTTP clients

`Tests\Support\Http\FixtureRouter` is a `MockHttpClient` response factory keyed
by **method + path** relative to the API base (`/api/v10` for Discord, `/v2`
for Miro). Paths may contain `{placeholders}`; a route may also pin query
parameters (`/boards/{board_id}/items?cursor=…`), and the route pinning the
most matching query parameters wins. Unregistered routes throw a
`LogicException` naming the request, so a missing fixture never looks like an
empty API answer.

`IntegrationFixtureRouters::discord()` / `::miro()` return routers with the
happy-path routes registered. Register a route again to override it, e.g. for
error handling:

```php
$router = IntegrationFixtureRouters::discord()->on(
    'GET', '/guilds/{guild_id}/channels', 'rate_limited.json', 429,
    IntegrationFixtureRouters::DISCORD_RATE_LIMIT_HEADERS,
);
$client = new MockHttpClient($router, IntegrationFixtureRouters::DISCORD_BASE_URI);
```

`$router->getRequests()` lists what was called, and `decodedFixture()` loads a
fixture as an array for assertions.

## Service ids in the test environment

`config/services_test.yaml` defines `discord.client` and `miro.client` as
`MockHttpClient`s backed by the routers above (also exposed as
`tests.integrations.discord.fixture_router` / `tests.integrations.miro.fixture_router`),
plus the `HttpClientInterface $discordClient` / `$miroClient` autowiring
aliases.

The production clients are expected to be **scoped HTTP clients** declared
under `framework.http_client.scoped_clients` with exactly these ids
(`discord.client`, `miro.client`), in non-test config. They do not conflict
with this file: service definitions from `services_test.yaml` take precedence
over definitions registered by the FrameworkBundle extension, so in the test
env the framework's `UriTemplateHttpClient` (and profiler tracing) wrap the
mock instead of a real transport. Requests made through
`$container->get('discord.client')` therefore keep resolving relative paths
against the base URI and are answered from these fixtures. Do not add
`mock_response_factory` to `framework.http_client`; it would replace every
client, Google's included.
