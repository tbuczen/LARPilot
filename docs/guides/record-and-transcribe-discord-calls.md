# Record and transcribe Discord calls

Planning calls are where many story decisions are made. You don't need
LARPilot to keep them: ready-made Discord bots already record calls and turn
them into text with each line marked by who said it. This guide shows how to
use them on their own today.

This guide is for **LARP organizers**. LARPilot can import Craig recordings
and turn them into a summary and transcript for you (see [Putting a transcript
into LARPilot](#putting-a-transcript-into-larpilot)).

> **Before you record anyone**
>
> - **Tell everyone first, every time.** Say it out loud at the start of the
>   call and write it in the channel. In the EU (GDPR) you need people to know
>   they are being recorded and why, and to be able to say no.
> - Anyone who doesn't want to be recorded should leave the voice channel, or
>   you remove their track before sharing anything (see Craig below: every
>   person is a separate file, so this is easy).
> - Keep recordings only as long as you need them, and share transcripts only
>   with the organizer team.

## Which bot should I use?

| | **Craig** | **NotesBot** | **DiscMeet** |
|---|---|---|---|
| What it does | Records each speaker as a separate audio file | Records, transcribes and summarizes | Records, transcribes and summarizes |
| Polish | Transcripts via Whisper (paid tier) | Yes, detected automatically | "100+ languages" (check Polish on a test call) |
| Who said what | Always – one file per person | Speaker labels | Speaker labels (one track per person) |
| Where the result goes | Download link in Discord DM; optional automatic upload to Google Drive, OneDrive or Dropbox | A thread under the text channel, plus a `.txt` file and a web dashboard | Thread in Discord and a web dashboard |
| Free | Yes: up to 6 h per call, kept 7 days | 30-minute trial | 2 recordings of 1 h |
| Paid | $1 / $4 / $8 per month | $3–$40 per month (5–100 h) | from $9.99 per month |
| Open source | Yes ([CraigChat/craig](https://github.com/CraigChat/craig)) | No | No |

Prices and limits come from each bot's own website in October 2026. They
change, so check before you pay.

**Our suggestion:** use **Craig**. It is free, has been around for years, is
open source, and gives you one clean audio file per person, which is the best
starting point for an accurate Polish transcript. Use NotesBot or DiscMeet if
you want a summary in Discord without any extra steps and are fine with a
paid service.

## Craig

### 1. Add Craig to your server (once)

1. Open [craig.chat](https://craig.chat/) and click **Invite Craig**.
2. Pick your Discord server. You need the **Manage Server** permission.
3. Optional: limit who can start recordings with
   `/server-settings access-role add`.

### 2. Record a call

1. Join the voice channel.
2. In any text channel type `/joinchannel`. Craig joins your voice channel and
   changes its nickname to **[RECORDING]**, so everyone can see it is
   recording. If Craig can't change its nickname, it refuses to record.
3. During the call, `/notemessage` adds a timestamped note, for example
   "decision: Faction X betrays the king". Notes appear in the download.
4. When you are done, type `/stop`.

Craig sends you a private message with a **download link**. The recording is
kept for **7 days** on the free plan (14–30 days for supporters), so download
it soon.

### 3. Download

On the download page choose **Multi-track**. You get a `.zip` with one audio
file per person, named after their Discord name.

- To transcribe on your own computer, **FLAC** gives the best quality.
- To upload to LARPilot (below), choose **Ogg Vorbis** or **MP3**. Files over
  25 MB per person are compressed or split for you (see [Long
  calls](#long-calls)); MP3 works even when the server has no ffmpeg.

To leave someone out, delete their file before you share or transcribe.

### 4. Save recordings to Google Drive automatically (optional)

Any Craig supporter (from $1 per month) can connect Google Drive, OneDrive or
Dropbox in the [Craig dashboard](https://craig.chat/). Each finished recording
is then uploaded to a folder called **Craig**. Use `/bless` on your server so
recordings started by other organizers are uploaded too.

Tip: share that **Craig** folder only with your organizer team.

### 5. Turn the recording into text

**Option A – Craig does it (Tier 3, $8 per month).** Open the recording's
download page and use the **Transcription** section. You can download WebVTT,
SRT or plain text, and transcripts can be included in the automatic Drive
upload. Check the first one: Craig doesn't document how it picks the language.

**Option B – free, on your own computer.** Whisper is a free speech-to-text
program that handles Polish well. It is slow without a good graphics card
(about as long as the call itself, or longer).

1. Install [Python 3](https://www.python.org/downloads/) and
   [ffmpeg](https://ffmpeg.org/download.html).
2. Install Whisper:

   ```bash
   pip install -U openai-whisper
   ```

3. Unzip the Craig download, open a terminal in that folder and run:

   ```bash
   whisper *.flac --language pl --model large-v3 --output_format srt --output_dir transcripts
   ```

   Use `--model medium` if it is too slow. Leave out `--language pl` only if
   the call was mostly in another language.

4. You now have one `.srt` file per person. Save this as `merge.py` next to the
   `transcripts` folder to get one transcript in time order:

   ```python
   import re
   import sys
   from pathlib import Path

   lines = []
   for srt in Path(sys.argv[1]).glob("*.srt"):
       speaker = re.sub(r"^\d+-", "", srt.stem)
       for block in srt.read_text(encoding="utf-8").strip().split("\n\n"):
           rows = block.splitlines()
           if len(rows) < 3:
               continue
           start = rows[1].split(" --> ")[0].split(",")[0]
           lines.append((start, speaker, " ".join(rows[2:])))

   for start, speaker, text in sorted(lines):
       print(f"[{start}] {speaker}: {text}")
   ```

   ```bash
   python merge.py transcripts > transcript.txt
   ```

   The result looks like:

   ```text
   [00:12:31] anna: To może frakcja Kruków powinna o tym wiedzieć wcześniej?
   [00:12:36] tomek: Zgoda, dopiszę to do wątku.
   ```

### Running your own Craig

Craig is open source (ISC license), so you can run your own copy if you don't
want recordings on someone else's servers. It needs a server that runs Node.js
and Docker all the time. See [CraigChat/craig](https://github.com/CraigChat/craig);
this is a job for whoever runs your servers, not a quick setup.

## NotesBot

A hosted bot that records, transcribes and summarizes a call, then posts the
notes in a thread under the text channel. It detects the language by itself and
lists Polish as supported (not in its highest-accuracy group, so do a test
call first).

1. Add it from [notesbot.io](https://www.notesbot.io/discord-call-transcription).
2. Start it in your voice channel as its instructions say. The notes arrive as
   a thread, with a `.txt` transcript.
3. With `/config` you can post plain messages instead of a thread, and choose
   who can open recordings (by default everyone who was in the call).

The audio (MP3) is deleted after 14 days. Remember to tell people before you
start: we couldn't find a built-in recording notice in its documentation.

## DiscMeet

Similar to NotesBot: records each person on their own track, posts a
transcript and summary, and keeps them on a dashboard where you can delete
them. The paid plan has an API and webhooks, which LARPilot could use later.
Start at [discmeet.com](https://discmeet.com/discord-transcription).

## Putting a transcript into LARPilot

Organizers can import a Craig recording straight into LARPilot and get
something close to "Google Meet + Gemini" notes: a summary, the decisions, who
does what, and the full transcript with real names.

### Once per LARP: defaults

Open **Administration → Call transcripts**. The **Defaults for this LARP** card
sets what every new upload starts with (you can still change it per upload):

- **Write an AI summary** – on by default.
- **Save a Google Doc copy in** – appears when Google Drive is connected. Pick a
  Drive folder first: **Integrations → Google Drive → Mappings**, pick the folder
  with the Google file picker, and it shows up in this list.
- **Post the summary to** – appears when Discord is connected and the LARP
  watches at least one channel. Organizer-only channels are listed first;
  player-facing channels are in their own group because **players will see the
  post**. Pick one of those only on purpose. If the page shows **Update bot
  permissions**, the LARPilot bot was invited before it could post: click it and
  authorize the invite again (see [Connect your Discord server](connect-discord.md)).

### Every call

1. Upload the Craig `.zip` (Ogg Vorbis or MP3, see above). Pick the language:
   Polish is the default. Check the summary, Google Doc and Discord choices.
2. LARPilot lists the speakers it found and proposes who each one is: it uses
   the Discord account linked to a participant's LARPilot profile (Craig's
   `info.txt` lists everyone's Discord id) or, if Discord is connected, the
   server's member list. Change the proposal if it is wrong, or keep the raw
   Discord name. Untick anyone who did not agree to be recorded: their audio is
   deleted and never transcribed.
3. Each person is transcribed separately in the background, then merged into
   one transcript in time order, with the names you confirmed:
   `[00:12:31] Anna Nowak (Gracz): …`. If the zip already contains Craig's own
   transcripts, LARPilot uses them instead.
4. With the summary on, OpenAI then writes, in the call's language: a short
   summary, **decisions**, **action items** (with the person, when someone was
   named), **open questions**, and **mentioned story objects** – characters,
   factions, threads, quests, places, items and events whose titles came up,
   linked to their pages. Long calls are summarized part by part and merged.
5. The summary goes at the top of a knowledge document, the transcript below
   it. **Transcript** on the list opens it. It is visible to organizers only.
   The audio is deleted as soon as the transcript is ready.
6. If you picked a folder, a Google Doc named **"<call name> – <date>"** with
   the same content appears there: **Open in Google Docs** on the list.
7. If you picked a channel, the LARPilot bot opens a thread named after the
   call in that channel and posts the summary (in several messages if it is
   long, without pinging anyone) with links to the transcript and the Google
   Doc. **Open in Discord** on the list jumps to it.

The **Results** column shows each of these separately: *In progress*, *Ready*
or *Failed*. A failed summary, Google Doc or Discord post never loses the
transcript: read the reason under it, fix it (for example reconnect Google Drive
or update the bot permissions) and click **Retry**.

### Long calls

OpenAI accepts at most 25 MB per file. Bigger speaker tracks are handled for
you: if the server has **ffmpeg**, LARPilot compresses the track (about 11 MB
per hour) and, if needed, cuts it into parts; without ffmpeg, **MP3** tracks
are still cut into parts. The timestamps stay correct. Without ffmpeg, a FLAC or
Ogg track over 25 MB fails with a "too large" message for that person – choose
MP3 on the Craig download page, or ask whoever runs LARPilot to install ffmpeg.
If OpenAI is busy or rate-limited, LARPilot waits and tries again a few times
before giving up on a speaker.

### What next

Transcripts are raw material. Copy the decisions that matter into the story
objects they are about, or into a **Lore Document** (Story → Lore Documents),
which the whole writing team can find and the AI Assistant can read.

If you used NotesBot or DiscMeet, their notes arrive in a Discord thread; when
that channel is watched by LARPilot, the thread shows up in the **Discord
inbox** like any other conversation.

## What's coming

Next step: reading Craig's Google Drive folder automatically, so you don't
have to download and upload the `.zip` yourself. Progress is tracked in
[tbuczen/LARPilot#192](https://github.com/tbuczen/LARPilot/issues/192).
