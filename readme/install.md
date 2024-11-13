### Install RedSeaNet Social e-commerce platform(PHP) in Local

To install Docker in your computer, You can get help in https://docs.docker.com/engine/install/;

---

1. Clone the repository;

2. Open your Terminal, and cd your just cloned the repository;

3. cd ./docker && docker compose up -d  //it will 

4. mkdir -p ./app/config/ && cp -r ./docker/adapter.yml ./app/config/

5. docker cp ./docker/redseanetshop.sql mysql_c:/

6. docker exec -it mysql_c mysql -u root --password=Testing%%2029

7. use redseanetshop 

8. source /redseanetshop.sql

---

