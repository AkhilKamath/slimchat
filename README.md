# SLIM PHP CHAT APP

This is an example of a RESTful api created in php using the slim4 framework.

## HOW TO USE

### Setup env
- configure `DB_PATH` and `JWT_KEY` in `app/env/.env`
- if using docker, add `127.0.0.1       slimchat.local` to `/etc/hosts`

### Start server
There are 2 ways to use it. You can either use via docker or manually

#### Manual
Few changes required for manual setup
  ```
  DB_PATH=<absolute-path-to-this-project-root>/app/src/Database/database.db
  JWT_KEY=my_secret_key
  BASE_URI=http://localhost:8000
  ```
**To run the app:**
```
$ cd app
$ composer start
```
**To run unit tests:**\
The server needs to be running for running unit tests
```
composer test
```

#### Docker
Few changes required for docker setup
- add hosts to /etc/hosts
  ```
  $ sudo vi /etc/hosts
  ```
  add the following line -
  ```
  127.0.0.1       slimchat.local
  ```
- .env changes
  ```
  DB_PATH=/app/src/Database/database.db
  JWT_KEY=my_secret_key
  BASE_URI=http://slimchat.local:8000
  ```
**To run the app:**\
It will create 2 services - 1 nginx and 1 php.
```
$ docker compose up -d
```
**To run unit tests:**\
The server needs to be running for running unit tests
```
composer test
```

### Use Postman

You can download the postman collection shared with you to run the tests

**NOTE**\
- All routes except the **Create User** route will require a Bearer Token to be set with the request.
- When you create a user using the **Create User** route, you will get a token in the response.
- Please use this token for all subsequent reuqests.

### Endpoints:

#### USERS:

- Create User: `POST /api/v1/users`

- Get User: `GET /api/v1/users/{id}`

- Get Users: `GET /api/v1/users`

- Delete User: `DELETE /api/v1/users/{id}`


#### CHAT:

- Create Chat: `POST /api/v1/chats`

- Add User to Chat: `POST /api/v1/chats/{chatId}/users`

- Get a Chat: `GET /api/v1/chats/{chatId}`

- Get all Chats: `GET /api/v1/chats`

- Get Chats of User: `GET /api/v1/chats/user/{userId}`

- Get Users in Chat: `GET /api/v1/chats/{chatId}/users`

- Delete Chat: `DELETE /api/v1/chats/{chatId}`


#### MESSAGE:

- Create Message: `POST /api/v1/chats/{chatId}/{userId}/messages`

- Get messages of Chat: `GET /api/v1/chats/{chatId}/messages`

- Get messages of Chat with offset: `GET /api/v1/chats/{chatId}/messages?lastMessageId=5`
