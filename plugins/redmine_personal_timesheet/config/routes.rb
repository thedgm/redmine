if Rails::VERSION::MAJOR >= 3
  RedmineApp::Application.routes.draw do
    match 'personal_timesheet/index' => 'personal_timesheet#index'
    match 'personal_timesheet/context_menu' => 'personal_timesheet#context_menu'
    match 'personal_timesheet/report' => 'personal_timesheet#report'
    match 'personal_timesheet/reset' => 'personal_timesheet#reset', :via => :delete
  end
end
