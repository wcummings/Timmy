# Timmy

<img src="timmy.jpg" width="300" />

## Setup

1. Copy files to your webroot
2. Initialize the DB

```bash
./bin/create_tables.sh
```

3. Add some players

```bash
./bin/shell.php
```
```php
$scoreboard = new Scoreboard();
$scoreboard->registerPlayer('Will');
```

4. Configure outbound slack webhook

5. Configure inbound slack webhook and set COMMANDER_WEBHOOK

6. Keep it real :sunglasses:

## Endpoints

* [/bullshitcard.php](bullshitcard.php) :: Webhook endpoint

## Hacking

```bash
./bin/shell.php
```
