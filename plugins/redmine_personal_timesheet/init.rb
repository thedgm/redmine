require 'redmine'

Redmine::Plugin.register :redmine_personal_timesheet do
  name 'Redmine Personal Timesheet plugin'
  author 'Liliya Bancheva'
  description 'This is a personal timesheet plugin for Redmine'
  version '0.0.1'
  requires_redmine :version_or_higher => '0.8.0'

  settings :default => {'list_size' => '5', 'precision' => '2'}, :partial => 'settings/personal_timesheet_settings'

  permission :see_project_timesheets, { }, :require => :member

  menu :top_menu, :timesheet, {:controller => 'personal_timesheet', :action => 'index'}, :caption => "Personal"
end
