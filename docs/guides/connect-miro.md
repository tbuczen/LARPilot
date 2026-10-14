# Connect Miro

If your team maps the story on Miro boards – who knows whom, which faction
lives where, how a plot unfolds – you can connect those boards to your LARP.
Later you will be able to link board items to story objects and import a story
map into LARPilot.

This guide is for **LARP organizers**. It takes a couple of minutes.

> **Before you start**
>
> - You need to be an **organizer** of the LARP in LARPilot. Story writers can
>   link sources later, but only organizers can connect or disconnect Miro.
> - You need a Miro account that is a member of the team that owns the boards.
> - One LARP connects to **one** Miro team. Each LARP picks its own boards.

## 1. Sign in with Miro

1. In your LARP, open **Administration → Integrations**.
2. On the **Miro** card, click **Connect**.
3. Miro opens. Sign in if asked, choose the **team** that owns your story
   boards, and allow access. LARPilot only asks to **read boards**; it cannot
   create, change or delete anything in Miro.
4. Miro sends you back to LARPilot. The Miro card now shows **Connected**.

![The Integrations page with the Miro card connected](img/integrations-hub-connected.png)

## 2. Pick boards

Connecting does not read any board yet. You choose which boards belong to this
LARP.

1. On the Miro card, click **Settings**, then **Manage boards**.
2. Every board of the connected team is listed. Click **Add to LARP** on each
   board you want. Its status changes to **Selected**.
3. Click **Remove from LARP** to drop a board again.

![Picking the boards that belong to this LARP](img/miro-boards.png)

<details>
<summary>On a phone</summary>

![The board picker at phone width](img/miro-boards-mobile.png)

</details>

Selected boards appear under **Sources** on the Miro page. Click a board name
to open it in Miro.

## 3. What happens next

> **Coming soon:** browsing the items and frames of your boards inside
> LARPilot, and linking them to story objects with name and tag suggestions
> (Miro board browser). Until it ships, the **Browse** tab stays empty for
> Miro. You can already link a whole board or a single item to a story object
> by pasting its Miro link on the story object's **Sources** tab – see
> [Connect Discord → Link a conversation](connect-discord.md#5-link-a-conversation-to-a-story-object);
> it works the same way for Miro links.

Miro does not tell LARPilot when a board changes, so boards are read when you
open them in LARPilot and kept for a short while, rather than synced in the
background.

## 4. Keep the connection alive

Miro gives LARPilot access for one hour at a time, and LARPilot renews it on
its own. You do not need to do anything as long as the connection keeps
working.

If Miro stops accepting the renewal – for example because someone removed
LARPilot under *Profile settings → Your apps* in Miro, or left the team –
LARPilot shows a yellow banner:

> *LARPilot can no longer access your Miro team. Reconnect Miro to keep reading
> the selected boards.*

![The reconnect banner on the board picker](img/miro-reconnect.png)

Click **Reconnect Miro** (or **Reconnect** on the Integrations page) and sign
in again. Your board choices are kept.

## 5. Tag your boards for a later import

A future update will turn a Miro story map into LARPilot story objects and
relations. Nothing is written without your confirmation: LARPilot first shows
a preview and you tick what to import. Re-running it only shows what changed.

You can prepare your boards today so the import can make good suggestions:

- **Tag cards and sticky notes with their type.** Use Miro tags named exactly
  `Character`, `Faction`, `Thread`, `Quest`, `Place`, `Item` or `Event`.
  A tagged card is matched to an existing story object of that type with the
  same name, or offered as a new one.
- **Use the same names as in LARPilot.** A card called "Captain Mira Vale"
  links to the character "Captain Mira Vale". Untagged cards are only matched
  when the title is an exact match; others are listed as *unmapped* for you to
  decide.
- **Name frames after factions, threads or places.** A frame called
  "House Ardenne" is matched to the faction of that name, and tagged
  characters inside it are shown as possible members.
- **Label connectors with the relationship.** Write the relation on the
  arrow's caption, for example `friend`, `enemy`, `family` or `ally`.
  Connectors with an unknown caption are flagged for you to choose a type –
  LARPilot never guesses.

## Disconnect

On the Miro page, click **Disconnect** and confirm. LARPilot stops reading
your Miro team and forgets the selected boards and everything cached from
them. Links already added to story objects stay. Nothing changes in Miro.
You can also remove LARPilot from your Miro account under *Profile settings →
Your apps*.

More in the [Q&A](integrations-faq.md).

## For the server admin

Hosting LARPilot yourself? The Miro app (with expiring tokens, the `boards:read`
scope and the redirect URL) is created once per installation. See
[Deployment → Miro integration](../DEPLOYMENT.md#miro-integration) for the
steps and the `OAUTH_MIRO_CLIENT_ID` / `OAUTH_MIRO_CLIENT_SECRET` secrets.
