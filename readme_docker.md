# Part 1: ENVIRONMENTAL SETUP FOR SERVER
- Git
- Config ssh
- Docker
- Docker-compose
- Setup proxy
# Part 2: SETUP DOCKER
###  Step 1: pull code jsc.
    git clone ssh://gituser@jawhm.net:8822/var/lib/git/repository/jsc.git
###  Step 2: cd to jsc.
    cd jsc
### Step 3: Start docker-compose with the command below.
    docker-compose up --build -d
### Step 4: Attach to container
	docker exec -it <mycontainer> bash
### Step 5: Update và install verdor
    composer install && composer update && composer dump-autoload
### Step 6: Copy folder com from root  to vendor 
    cp -r com vendor/yiisoft/yii2/
### Step 7: change permissions for 2 folders vendor and api
    chmod -R 775 vendor && chmod -R 777 api/
### Step 8: import file database jsc.sql trong root
### Step 9: Config file connect database in folder common/config/main-local.php.
    'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=db;dbname=jsc',
            'username' => 'root',
            'password' => 'pass_container_mysql',
            'charset' => 'utf8',
			'enableSchemaCache' => false,
            'schemaCacheDuration' => 3600,
            'schemaCache' => 'cache',
        ],

### Step 10: Check the result
    http://jsc.jawhm.org
### Step 11: Change the permission for the api folder to 775 to be secure.
    chmod -R 775 api/