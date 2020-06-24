# Scoreboards Information
This README explains how to use the scoreboard system in the new Practice Core system.

## Table of Contents
[TOC]

## Scoreboard Types
The default scoreboard types that are used by the internal Practice Core plugin are:

- `scoreboard.spawn.default` - Used when a  player is at spawn and not in a queue.
- `scoreboard.spawn.queue` - Used when a player is at spawn and in a queue for a duel.
- `scoreboard.ffa` - Used when a player is in a FFA arena.
- `scoreboard.duel.player` - Used when a player is in a duel.
- `scoreboard.duel.spectator` - Used when the player is spectating a duel.

**DO NOT EDIT THE SCOREBOARD TYPE NAMES IN THE SCOREBOARDS.YML FILE, IF YOU DO, YOUR PRACTICE CORE WILL NOT WORK.**

## Editing the Display
You can change what the scoreboards display and which
line number they display on by changing the current line number under the scoreboard that you want to edit.

**Example:**
```yaml
scoreboard.spawn:
	line-1: "Line 1"
	line-2: "Line 2"
```

**DISCLAIMER:** The maximum of lines on a scoreboard is 15. **ANY LINE NUMBER PAST 15 WILL NOT SHOW UP ON THE SCOREBOARD**

 ---
You can also change the text on the corresponding line on the scoreboard.

**Example:**
```yaml
scoreboard.spawn:
	line-1: "Line 1"
	line-2: "Online-Players"
```
---

## Displaying Statistics
There are certain statistics that you can display on a given line by using a set of curly-braces and defining the type of statistic that you want to display.

**Example:**
```yaml
scoreboard.spawn:
	# Displays how many online-players there are.
	line-1: " Online-Players: {online} "
```

** DISCLAIMER: ** Certain statistics only work when the player is in a certain scenario or has a specific scoreboard type.

## List of Statistics
This provides the list of all the types of statistics that the scoreboard can display based on the corresponding scoreboards that these statistics work on.

 ---
**Any Scoreboard** (These Statistics Work for Any Scoreboard)**:**
- `{kills}` - Lists the number of kills the player has.
- `{deaths}` - Lists the number of deaths the player has.
- `{cps}` - Lists the clicks-per-second of the player.
- `{online}` - Displays the current number of players on the server.
- `{in.queues}` - Displays the current number of players in a queue for a duel.
- `{in.fights}` - Displays the current number of players in a duel.
- `{ping}` - Displays the current player's ping.
- `{name}` - Displays the current player's name.
---

**scoreboard.spawn.queue:**
- `{queue}` - Displays the current queue the player is in.

---
**scoreboard.ffa:**
- `{ffa.arena}` - Displays the current ffa arena the player is playing.
- `{ffa.arena.players}` - Displays the current number of players playing in the arena.
---
**scoreboard.duel:**
- `{opponent}` - Displays the name of your opponent.
- `{opponent.cps}` - Displays the CPS of your opponent.
- `{opponent.ping}` - Displays the Ping of your opponent.
- `{duel.arena}` - Displays the name of the duel arena.
- `{duration}` - Displays the current duration of the duel.
- `{spectators}` - Displays the number of spectators watching the duel.
- `{kit}` - Displays the current duel kit.
---
**scoreboard.duel.spectator:**
- `{duration}` - Displays the current duration of the duel.
- `{spectators}` - Displays the number of spectators watching the duel.
- `{kit}` - Displays the current duel kit.
- `{duel.arena}` - Displays the name of the duel arena.
---