# Discord & Miro: questions and answers

Short answers for organizers. Step-by-step setup is in
[Connect your Discord server](connect-discord.md) and
[Connect Miro](connect-miro.md).

## Who can connect, disconnect and link?

Only **organizers** of a LARP can connect or disconnect Discord and Miro and
choose channels or boards. **Story writers and organizers** can link sources to
story objects on the **Sources** tab.

## Why does it take a few minutes for new Discord threads to show up?

Discord does not notify websites when something new is posted, so LARPilot
has to ask. It checks your watched channels **about every 5 minutes** in the
background. Discord also limits how many questions a bot may ask per second,
so on a large server LARPilot spreads the reading out and may need a little
longer. A thread started now usually shows up within a few minutes.

**Sync now** on a source asks for an earlier check. It still runs in the
background, so give it a minute.

> Reading threads from Discord (thread sync) is being finished. Until it
> ships, channels you choose are saved but nothing is read yet.

## Why aren't private threads read?

Private threads are meant for the people invited to them – often a writer
talking to one player, or a sensitive safety topic. LARPilot never reads them,
even when they sit in a watched channel. The bot also only has permission to
view channels and read message history, which does not include private
threads. If something in a private thread matters for the story, move the
outcome to a normal thread or link the private thread by pasting its link on
the story object's **Sources** tab (LARPilot keeps only the link).

## What does LARPilot store from Discord?

LARPilot is an index, not a copy of your chat.

- **Every watched channel:** thread and post titles, links, authors, dates and
  forum tags.
- **Organizer-only channels:** additionally a **short excerpt** of each thread,
  so you can recognise it without opening Discord.
- **Player-facing channels:** no excerpts at all – only the metadata above.
- **Never:** full conversations, private threads, channels you did not switch
  on, direct messages or voice channels.

## What does LARPilot store from Miro?

The names and links of the boards you select. When board browsing arrives it
will also keep the titles, tags and positions of board items so you can search
and link them. Your Miro login is stored encrypted and is used only to read the
boards you selected.

## Does LARPilot post or change anything in Discord or Miro?

No. The Discord bot can only view channels and read message history, and the
Miro connection can only read boards. Writing back is not part of this
version.

## Can players see what LARPilot reads?

No. Everything on the Integrations pages and the **Sources** tab is for the
organizing team. On top of that, excerpts are only kept for organizer-only
channels, so LARPilot never holds text from a player channel that it could
show in the wrong place.

## Can two LARPs share one Discord server?

Yes. Each LARP connects the same server and chooses its own channels. A LARP
never sees the other LARP's channel choices or synced threads.

## What happens when I disconnect Discord?

When you click **Disconnect** and confirm:

- the watched channels of **this LARP** and everything synced from them are
  deleted from LARPilot;
- the excerpts saved on this LARP's links are removed;
- the links on story objects **stay**, with their title and Discord link, so
  you can still open the conversation;
- the bot **leaves your Discord server** – unless another LARP is still
  connected to the same server, in which case it stays for that LARP.

Nothing in Discord itself is deleted.

## What happens when I disconnect Miro?

The selected boards of this LARP and everything cached from them are deleted
from LARPilot. Links on story objects stay. Nothing in Miro changes.

## The Integrations page says "Needs reconnect". What do I do?

LARPilot lost access:

- **Discord:** the bot was removed from the server or lost its permissions.
- **Miro:** someone removed LARPilot from their Miro apps, or the connection
  expired.

Click **Reconnect** and go through the connect steps again. Your channel and
board choices are kept (unless you switch Discord to a different server).

## Where can I see if something went wrong?

Open the integration and go to the **Activity** tab. It shows whether the
connection works, when each source was last read and whether the last read
succeeded, partly succeeded or failed. Failed items are retried on the next
run.
