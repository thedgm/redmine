require 'redmine'
## Taken from lib/redmine.rb
#if RUBY_VERSION < '1.9'
#  require 'faster_csv'
#else
#  require 'csv'
#  FCSV = CSV
#end

if Rails::VERSION::MAJOR < 3
  require 'dispatcher'
  object_to_prepare = Dispatcher
else
  object_to_prepare = Rails.configuration
  # if redmine plugins were railties:
  # object_to_prepare = config
end

object_to_prepare.to_prepare do
  require_dependency 'principal'
  require_dependency 'user'
  User.send(:include, RedminePersonalTimesheet::Patches::UserPatch)
  require_dependency 'project'
  Project.send(:include, RedminePersonalTimesheet::Patches::ProjectPatch)
  # Needed for the compatibility check
  begin
    require_dependency 'time_entry_activity'
  rescue LoadError
    # TimeEntryActivity is not available
  end
end

Redmine::Plugin.register :redmine_personal_timesheet do
  name 'Redmine Personal Timesheet plugin'
  author 'Liliya Bancheva'
  description 'This is a personal timesheet plugin for Redmine'
  version '0.0.1'
  requires_redmine :version_or_higher => '0.8.0'
  settings :default => {'list_size' => '5', 'precision' => '2'}, :partial => 'settings/personal_timesheet_settings'
  permission :see_project_timesheets, { }, :require => :member
  menu :top_menu, :personal_timesheet, {:controller => 'personal_timesheet', :action => 'index'}, :caption => "Personal"
end
