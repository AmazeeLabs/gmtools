# gmtools

deploy-io-to-hetzner-za
=======================

This command helps to take an AmazeeIO dev site and deploy it on Old School Hetzner ZA hosting. There tool relies on the 'hetznerpaths' key in the YAML file. See gmttools.example.yml for example layout.

  * deploys: This is a directory that will contain a copy of the app each time a deploy runs
  * webroot: This is the webroot of the server. DO NOT use ~/public_html - use the full path
  * localwebpath: This will almost always be '/public_html/web/' 
  * localconfpath: This will almost always be '/public_html/config/'
  * empty: This is used for fast directory cleaning. Use '~/empty/' and it will be created.
  * devhost: The hostname of the AmazeeIO dev server: 'something.amazee.io'
  * devhostdir: The full directory on the AmazeeIO dev server '/var/www/some_az_client_site/public_html'
  * exclude: - This will almost always be the following
    * '.git'
    * 'node_modules'
  * defaultsitefiles: Files in here will be hard copied into 'sites/default/' as overrides
  * drupalconfig: This should be the FULL PATH to a directory that will contain the Drupal config. Do not include '/sync'. EG for "/home/fred/config/" do not use "/home/fred/config/sync"
