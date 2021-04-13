# Dranzer

#### Scaffold production ready Laravel apps in seconds!

![](https://i.imgur.com/E1UYgFh.png)

### Features

✓ Build API

✓ Generate Authentication (UI, Breeze and Fortify)

✓ Role Based Authorization (Orchid Admin Panel)

✓ Compliance (Cookies Consent)

✓ Build Controllers

✓ Schema Manager

✓ Eloquent Relations Manager

✓ Configure Nginx

✓ Install Development Packages (DebugBar, Decomposer, IDE Helper)

✓ Configure Exceptions (Error Pages, Exceptions Handler)

✓ Setup Tailwind

✓ Setup Vue

✓ Create Admin Panel (Orchid)

✓ Custom Blade Layout

✓ Custom Blade Partials

✓ Configure Linters (Eslint)

✓ Setup Logging

✓ Create Mailables

✓ Configure Middlewares

✓ Create Notifications 

✓ Create Jobs

✓ Configure Headers and Proxies

✓ Create Scheduled Tasks

✓ Configure Form Validation


### Installation

#### Requirements

- PHP >= 7.4
- MongoDB >= 4.4
- Composer
- Yarn or NPM

**1. Clone this repository:**

`git clone https://github.com/level39/dranzer`

**2. cd into the cloned repository:**

`cd dranzer`

**3. Install dependencies:**

`composer install && yarn`

**4. Compile assets**

- Production:
  
    `yarn run prod`

- Development:
  
    `yarn run hot`

**5. [Create a GitHub OAuth app](https://github.com/settings/applications/new)**

Dranzer uses GitHub authentication. Create an OAuth app and edit the `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` environment variables to use the ones generated by your app.

**6. Set your projects directory:**

Dranzer places the generated project to the project directory defined in the .env file. Edit the **`PROJECTS_DIR`** env variable to where you want the generated project.

**Done. Use your preferred dev environment to access the project.**

### Roadmap

- [ ] Scaffolding
  - [ ] Modules
      - [x] Custom
          - [x] Admin Panel
      - [ ] Framework
          - [x] API Resources
          - [x] Assets (Mix)
          - [x] Authentication
          - [x] Authorization
          - [x] Blade Templates
          - [ ] Broadcasting
          - [ ] Browser Tests
          - [ ] CSRF Protection
          - [ ] Cache
          - [x] Commands
          - [ ] Console Tests
          - [x] Controllers
          - [x] Eloquent Models
          - [x] Error Handling
          - [ ] Events
          - [ ] HTTP Tests
          - [ ] Localization
          - [x] Logging
          - [x] Mail
          - [x] Middleware
          - [x] Notifications
          - [ ] Queues
          - [x] Requests
          - [ ] Responses
          - [ ] Routing
          - [x] Schema Builder
          - [ ] Session
          - [x] Task Scheduling
          - [ ] URL Generation
          - [x] Validation
          - [x] Views
  - [ ] Packages
      - [x] Breeze
      - [ ] Cashier
      - [ ] Envoy
      - [x] Fortify
      - [x] Horizon
      - [ ] Jetstream
      - [ ] Passport
      - [ ] Sail
      - [x] Sanctum
      - [ ] Scout
      - [ ] Socialite
      - [x] Telescope
- [ ] Project Upgrade (Planned)

### License

Dranzer is licensed under the [MIT License](https://en.wikipedia.org/wiki/MIT_License).
