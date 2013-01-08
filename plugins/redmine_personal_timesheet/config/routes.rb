if Rails::VERSION::MAJOR >= 3
  RedmineApp::Application.routes.draw do
    match 'timesheet/index' => 'personal_timesheet#index'
    match 'timesheet/context_menu' => 'personal_timesheet#context_menu'
    match 'timesheet/report' => 'personal_timesheet#report'
    match 'timesheet/reset' => 'personal_timesheet#reset', :via => :delete
  end
else
  ActionController::Routing::Routes.draw do |map|
    map.connect 'timesheet/index', :controller => 'timesheet', :action => 'index'
    map.connect 'timesheet/context_menu', :controller => 'timesheet', :action => 'context_menu'
    map.connect 'timesheet/report.:format', :controller => 'timesheet', :action => 'report'
    map.connect 'timesheet/reset', :controller => 'timesheet', :action => 'reset', :conditions => { :method => :delete }
  end
end
