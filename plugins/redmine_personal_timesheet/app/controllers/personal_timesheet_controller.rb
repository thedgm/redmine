class PersonalTimesheetController < ApplicationController
  unloadable

  layout 'base'
  before_filter :get_list_size
  before_filter :get_precision
  before_filter :get_activities

  helper :sort
  include SortHelper
  helper :issues
  include ApplicationHelper


  def index
    @from = Date.new(Date.today.year, Date.today.month, 1).to_s
    @to = Date.today.to_s
    @requested_user = User.current
    PersonalTimesheet.const_set("MYREQUESTEDUSER", @requested_user.id)
    obj = request
    obj.query_parameters.each do |k,v|
      if k.eql?("name")
        @requested_user = User.find(:first, :conditions => ["login = ?", v])
        PersonalTimesheet.const_set("MYREQUESTEDUSER", @requested_user.id)
        puts v
      else
        PersonalTimesheet.const_set("MYREQUESTEDUSER", @requested_user.id)
      end
    end

    @timesheet = PersonalTimesheet.new
    @timesheet.allowed_projects = allowed_projects
    if @timesheet.allowed_projects.empty?
      render :action => 'no_projects'
      return
    end
  end

  def report

    begin
    @requested_user = User.find(PersonalTimesheet::MYREQUESTEDUSER)
  rescue
    @requested_user = User.current
    end
    @weekends = params[:weekend]
    if params && params[:timesheet]
      @timesheet = PersonalTimesheet.new( params[:timesheet] )
    else
      redirect_to :action => 'index'
      return
    end
    @timesheet.allowed_projects = allowed_projects
    if @timesheet.allowed_projects.empty?
      render :action => 'no_projects'
      return
    end
    if !params[:timesheet][:projects].blank?
      @timesheet.projects = @timesheet.allowed_projects.find_all { |project|
        params[:timesheet][:projects].include?(project.id.to_s)
      }
    else
      @timesheet.projects = @timesheet.allowed_projects
    end
    call_hook(:plugin_timesheet_controller_report_pre_fetch_time_entries,
      { :timesheet => @timesheet, :params => params })
    @timesheet.fetch_time_entries
#  
    # Sums
    @total = { }
    
	unless @timesheet.sort == :projectUser
   
    unless @timesheet.sort == :issue
      @timesheet.time_entries.each do |data,logs|
        @total[data] = 0
        
        if logs[:logs]
          logs[:logs].each do |log|
            @total[data] += log.hours
          end
        end
      end
    else
      @timesheet.time_entries.each do |project, project_data|
        @total[project] = 0
        if project_data[:issues]
          project_data[:issues].each do |issue, issue_data|
            @total[project] += issue_data.collect(&:hours).sum
          end
        end
      end
    end
    @grand_total = @total.collect{|k,v| v}.inject{|sum,n| sum + n}
	end
    send_csv and return if 'csv' == params[:export]
    render :action => 'details', :layout => false if request.xhr?
  end

  def context_menu
    @time_entries = TimeEntry.find(:all, :conditions => ['id IN (?)', params[:ids]])
    render :layout => false
  end

  
  private
  def get_list_size
    @list_size = Setting.plugin_redmine_personal_timesheet['list_size'].to_i
  end

  def get_precision
    precision = Setting.plugin_redmine_personal_timesheet['precision']

    if precision.blank?
      # Set precision to a high number
      @precision = 10
    else
      @precision = precision.to_i
    end
  end

  def get_activities
#    @activities = Enumeration::get_values('ACTI')
     @activities = TimeEntryActivity.all(:conditions => 'parent_id IS NULL')
  end

  def allowed_projects
    user = @requested_user.id
    if User.current.admin?
      if user == User.current.id
        return Project.find(:all, :order => 'name ASC')
      else
        return User.find(user).projects.find(:all, :order => 'name ASC')
      end
    else
      return User.current.projects.find(:all, :order => 'name ASC')
    end
  end
end
