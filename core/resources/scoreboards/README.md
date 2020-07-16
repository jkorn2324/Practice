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
	line-1: " Online-Players: {stat.online} "
```

** DISCLAIMER: ** Certain statistics only work when the player is in a certain scenario or has a specific scoreboard type.

## List of Statistics
This provides the list of the scoreboard statistics that this core allows.

 ---
#### Default Statistics:
These are the statistics that work for any scoreboard, regardless of game-modes that are enabled.
- `{stat.kills}` - Lists the number of kills the player has ever on the server.
- `{stat.deaths}` - Lists the number of deaths the player has ever on the server.
- `{stat.cps}` - Lists the clicks-per-second of the player.
- `{stat.online}` - Displays the current number of players on the server.
- `{stat.ping}` - Displays the current player's ping.
- `{stat.player.name}` - Displays the current player's name.
- `{stat.os}` - Displays the device operating system.
- `{stat.equipped.kit}` - Displays the current kit equipped by the player, defaults to `None` if they don't have a kit.
- `{stat.rank}` - Displays the current player's rank.
- `{stat.games.playing}` - Displays the number of players playing a game.
---
#### FFA Statistics:
These are the statistics that are added if FFA Arenas are enabled. 

These statistics work for any scoreboard:
- `{ffa.stat.players}` - Displays the total number of players playing in FFA.

These statistics ONLY work if the FFA scoreboard is currently displayed:
- `{ffa.stat.arena}` - Displays the current ffa arena the player is playing.
- `{ffa.stat.arena.players}` - Displays the current number of players playing in the arena.
- `{ffa.stat.arena.kit}` - Displays the ffa arena kit.
---