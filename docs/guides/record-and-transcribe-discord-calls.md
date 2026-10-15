# Record and transcribe Discord calls

Planning calls are where many story decisions are made. You don't need
LARPilot to keep them: ready-made Discord bots already record calls and turn
them into text with each line marked by who said it. This guide shows how to
use them on their own today.

This guide is for **LARP organizers**. LARPilot will later import these
recordings and transcripts for you (see [What's coming](#whats-coming)).

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

On the download page choose **Multi-track** and **FLAC**. You get a `.zip`
with one audio file per person, named after their Discord name.

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

## Putting a transcript into LARPilot today

Until the import is ready:

1. In your LARP, create a **knowledge document** (category of your choice,
   visible to organizers only).
2. Paste the transcript, or the bot's summary, into it.
3. Mention the characters, factions and threads it talks about, so the
   document shows up on their pages.

If you use Craig's Google Drive upload, you can also keep the transcript as a
Google Doc and attach it to the LARP through the Google Drive integration.

## What's coming

LARPilot will connect to these bots instead of building its own recorder:
you'll upload a Craig download (or point LARPilot at the Craig folder in
Google Drive), and LARPilot will transcribe each person's track in Polish,
merge it into one transcript, and save it as a LARPilot note, a Google Doc or a
Discord thread. Progress is tracked in
[tbuczen/LARPilot#192](https://github.com/tbuczen/LARPilot/issues/192).
