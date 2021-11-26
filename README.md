<p align="center">
    <a href="https://github.com/Xenophilicy/NaviCompass"><img src="https://github.com/Xenophilicy/NaviCompass/blob/master/icon.png"></img></a><br>
    <b>NaviCompass allows your players to transfer between your server network and worlds with simplicity.</b>
</p>

<p align="center">
    <img alt="GitHubrelease" src="https://img.shields.io/github/v/release/Xenophilicy/NaviCompass?label=release&sort=semver">
      <img alt="Stars" src= "https://img.shields.io/github/stars/Xenophilicy/NaviCompass?style=for-the-badge">
    <a href="https://discord.gg/6M9tGyWPjr"><img src="https://img.shields.io/discord/837701868649709568?label=discord&color=7289DA&logo=discord" alt="Discord" /></a>
</p>

This plugin uses FormAPI to easily display your servers and worlds listed in a simple interface. It's easy, all you need to do is enter your IPs and ports of the servers or the names of your worlds you would like to add, along with a label to show up as the name in the UI, all inside the config.yml file. After that, the plugin will do the rest and you're good to go! Players will either have option to interact with the selector item, or type your custom command in chat to access the UI. You also have the option to play sounds and show titles to the players during different events in the plugin! All of these things can also be customized in the config.yml file. Server networks simplified! 

### **NaviCompass now supports the WaterDog MCBE proxy!**

***

## Credits
* [Xenophilicy](https://github.com/Xenophilicy/) (original developer)
* [Vecnavium](https://github.com/Vecnavium/) (Maintainer for the longterm)

# Config
### Basic settings
```yaml
# Choose your transfer type:
# "external" will use an IP address and port to move players between SERVERS
# "internal" will use your preset command string to move players between WORLDS
# "hybrid" will include both transfer types
Transfer-Type: "hybrid"

# This is the command string that will be used to transfer players between WORLDS
# Don't worry about this if you're only using the EXTERNAL transfer type
# Use '{player}' for the player's name
# Use '{world}' for the world name
World-CMD: "multiworld tp {world} {player}"

# This is where you choose if world command should be run by the player or the console
# Don't worry about this if you're only using the EXTERNAL transfer type
# Options are: "console" and "player"
World-CMD-Mode: "console"
```

### Command settings
```yaml
  # Choose whether the command method should be enabled
  Enabled: true

  # This is name of the command that players will use to opent the UI
  Name: "/servers"

  # This is the command's description shown in the command window
  Description: "Open the server list!"

  Permission:
    # Choose whether to require players to have permission to use the command
    Enabled: false

    # This is the command's usage permission
    Node: "navicompass.use"
```
### Selector settings
```yaml
  # Choose whether the selector item should be enabled
  Enabled: true

  # Choose what item the server selector should be
  # Default item is Compass (345)
  Item: 345

  # Set the cooldown for using the compass to open the UI
  Cooldown:
    # Choose whether the cooldown should be enabled
    Enabled: true

    # Set the duration of the cooldown
    Duration: 1

    # Message sent to the player when the item is on cooldown
    # Set to false to disable the message
    Message: "§cCompass is on cooldown!"

  # Choose what slot of the player's inventory the selector should appear in
  # The range for this input is 0-35 (0-8 are the player's hotbar)
  Slot: 1

  # Choose whether to prevent players from dropping or moving the selctor to different slots
  Force-Slot: true

  # This is the text that will show on the selector in the player's inventory
  Name: "§l§aServer Selector"

  # This is the selector item's description that is displayed under the name
  Lore: "§bClick for Servers"
```
### UI settings
```yaml
  # This is the title of the transfer UI
  Title: "§9Server List"

  # This is the message that will show under the title on the transfer UI
  Message: "§aChoose a server to transfer to!"

  Status-Format:

    # Edit the format for online servers
    Online: "§aOnline"

    # This is where you edit the format for offline servers
    Offline: "§cOffline"

  Subtext:
    # This is the message displayed under each SERVER button
    # Use '{current-players}' for the server's current player count
    # Use '{max-players}' for the server's max player count
    # Use '{status}' to show whether the server is online or offline (the colors can be customized under Status-Format)
    Server: "§r{status} §8(§a{current-players}§8/§b{max-players}§8)"

    # This is the message displayed under each WATERDOG button
    # Don't worry about this if you're only using the EXTERNAL transfer type
    WaterDog: "§r§o§8Tap to transfer"

    # This is the message displayed under each WORLD button
    # Use '{current-players}' for the world's current player count
    World: "§r§o§8Tap to teleport §8(§a{current-players} §eplayer(s)§8)"
```

### Sound settings
```yaml
  # Choose what sound (if any) to play when opening the UI
  UI: "random.pop"

  # Choose what sound (if any) to play while transferring SERVERS
  Transfer: "mob.blaze.shoot"

  # Choose what sound (if any) to play while teleporting between WORLDS
  Teleport: "random.anvil_use"
```

### Titles settings
```yaml
  # Set the delay to show the title before transferring/teleporting
  # This value is in seconds (default is 2 seconds)
  # Set this to false or 0 for no delay
  Delay: 2

  # Set the formatting for the title shown to players when transferring SERVERS
  Transfer: "§a§oTransferring..."

  # Set the formatting for the title shown to players when teleporting between WORLDS
  Teleport: "§a§oTeleporting..."
```

### Entry Listing
This is where you list your servers/worlds to be displayed on the server UI

Server format: "ext:ServerTitle:ServerIP:ServerPort:ImageType:Path/URL"

WaterDog server format: "wd:ServerTitle:ServerName:ImageType:Path/URL"

World format: "int:WorldTitle:WorldAlias:ImageType:Path/URL"

```yaml
List:
 - "ext:§l§6Lobby §eServer:play.xenoservers.net:19132:url:file.xenoservers.net/Resources/GitHub-Resources/navicompass/lobby.png"
 - "ext:§l§2Prison §eServer:play.xenoservers.net:19133"
 - "wd:§l§3Sky§cBlock §eServer:skyblock:url:file.xenoservers.net/Resources/GitHub-Resources/navicompass/skyblock.png"
 - "wd:§l§bFactions §eServer:factions"
 - "int:§l§5Creative §eServer:creative:url:file.xenoservers.net/Resources/GitHub-Resources/navicompass/creative.png"
 - "int:§l§cKitPvP §eServer:kitpvp"
```
