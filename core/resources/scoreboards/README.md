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
You can also add empty lines to the scoreboard by setting the content inside of them as "".

**Example:**
```yaml
scoreboard.spawn:
    line-1: "Line 1"
    # Empty line on line 2.
    line-2: ""
    line-3: "Online-Players"
```

---
You can add color to the scoreboard lines by providing the color name based on the list.

**Example:**
```yaml
scoreboard.spawn:
    line-1: "{BLUE}Line 1"
    line-2: "{RED}Online-Players{RESET}"
```
Here is the list of all the colors you can use:

`{BLUE} {GREEN} {RED} {DARK_RED} {DARK_BLUE} 
{DARK_AQUA} {DARK_GREEN} {GOLD} {GRAY} {DARK_GRAY}
{DARK_PURPLE} {LIGHT_PURPLE} {YELLOW} {AQUA}
{BOLD} {WHITE}`

More line effects:

- `{RESET}` - Resets the colors of the text.
- `{BOLD}` - Makes the text bold.
- `{ITALIC}` - Makes the text italicized.
- `{UNDERLINE}` - Underlines the text.

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
- `{ffa.stat.players}` - Displays the current number of players in all the ffa arenas.
- `{ping}` - Displays the current player's ping.
- `{name}` - Displays the current player's name.
- `{os}` - Displays the device operating system.
---

**scoreboard.spawn.queue:**
- `{ranked}` - Displays whether or not the queue the player is in is ranked or not.
- `{kit}` - Displays the kit the player is queued for.

---
**scoreboard.ffa:**
- `{ffa.stat.arena}` - Displays the current ffa arena the player is playing.
- `{ffa.stat.arena.players}` - Displays the current number of players playing in the arena.
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