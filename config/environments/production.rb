# Settings specified here will take precedence over those in config/application.rb
RedmineApp::Application.configure do
  # The production environment is meant for finished, "live" apps.
  # Code is not reloaded between requests
  config.cache_classes = true

  #####
  # Customize the default logger (http://ruby-doc.org/core/classes/Logger.html)
  #
  # Use a different logger for distributed setups
  # config.logger        = SyslogLogger.new
  #
  # Rotate logs bigger than 1MB, keeps no more than 7 rotated logs around.
  # When setting a new Logger, make sure to set it's log level too.
  #
  # config.logger = Logger.new(config.log_path, 7, 1048576)
  # config.logger.level = Logger::INFO

  # Full error reports are disabled and caching is turned on
  config.action_controller.perform_caching = true
#  config.cache_store = :dalli_store
require 'action_dispatch/middleware/session/dalli_store'
Rails.application.config.session_store :dalli_store, :memcache_server => ['localhost', '127.0.0.1'], :namespace => 'sessions', :key => '_foundation_session', :expire_after => 20.minutes
  #config.cache_store = :dalli_store, 'localhost',
  #{ :namespace => "redmine_pro", :expires_in => 3600, :compress => true }
  
# Memcached
	#config.cache_store = :mem_cache_store, '127.0.0.1:11211', {:namespace => "production"}
	#config.cache_store = :mem_cache_store, "127.0.0.1:11211", "localhost:11211", "10.3.8.5:11211"

#	require 'memcached'
#	config.action_controller.cache_store =  :mem_cache_store, Memcached::Rails.new("localhost:11211")
#  config.cache_store = :mem_cache_store, Memcached::Rails.new("localhost:11211")

  # Enable serving of images, stylesheets, and javascripts from an asset server
  # config.action_controller.asset_host                  = "http://assets.example.com"

  # Disable delivery errors if you bad email addresses should just be ignored
  config.action_mailer.raise_delivery_errors = false

  # No email in production log
  config.action_mailer.logger = nil

  config.active_support.deprecation = :log
end
