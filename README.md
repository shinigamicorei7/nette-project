nette-project
=============
For install:

```bash
git clone https://github.com/shinigamicorei7/nette-project
cd nette-project
composer install
touch app/config/config.local.neon
```
### config.local.neon
```coffeescript
database:
  dsn: 'mysql:host=127.0.0.1;dbname=test'
	user: root
	password: secret
	options:
		lazy: yes

mail:
	smtp: true
	host: smtp.mailgun.org
	port: 465
	username: 'mail@udomain.mailgun.org'
	password: secret
	secure: ssl
```
