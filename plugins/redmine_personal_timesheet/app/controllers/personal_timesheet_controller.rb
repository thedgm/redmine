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
 SessionKey = 'timesheet_filter'

  def index
    load_filters_from_session

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
      puts "ima proekti"
      @timesheet.projects = @timesheet.allowed_projects.find_all { |project|
        params[:timesheet][:projects].include?(project.id.to_s)
      }
    else
      @timesheet.projects = @timesheet.allowed_projects
    end
    call_hook(:plugin_personal_timesheet_controller_report_pre_fetch_time_entries,
      { :timesheet => @timesheet, :params => params })
    save_filters_to_session(@timesheet)
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
    puts "grand total = "+@grand_total.to_s
	end
   # send_csv and return if 'csv' == params[:export]
   # render :action => 'details', :layout => false if request.xhr?
    respond_to do |format|
      format.html { render :action => 'details', :layout => false if request.xhr? }
      format.csv  { send_data @timesheet.to_csv, :filename => 'timesheet.csv', :type => "text/csv" }
    end
  end

  def context_menu
    @time_entries = TimeEntry.find(:all, :conditions => ['id IN (?)', params[:ids]])
    puts "time entries = "+Array(@time_entries).count
    render :layout => false
  end
 def reset
    clear_filters_from_session
    redirect_to :action => 'index'
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
    user = @requested_user != nil ? @requested_user.id : User.current.id#.id
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
  
  def clear_filters_from_session
    session[SessionKey] = nil
  end

  def load_filters_from_session
    if session[SessionKey]
      @timesheet = Timesheet.new(session[SessionKey])
      # Default to free period
      @timesheet.period_type = Timesheet::ValidPeriodType[:free_period]
    end

    if session[SessionKey] && session[SessionKey]['projects']
      @timesheet.projects = allowed_projects.find_all { |project|
        session[SessionKey]['projects'].include?(project.id.to_s)
      }
    end
  end

  def save_filters_to_session(timesheet)
    if params[:timesheet]
      # Check that the params will fit in the session before saving
      # prevents an ActionController::Session::CookieStore::CookieOverflow
      encoded = Base64.encode64(Marshal.dump(params[:timesheet]))
      if encoded.size < 2.kilobytes # Only use 2K of the cookie
        session[SessionKey] = params[:timesheet]
      end
    end

    if timesheet
      session[SessionKey] ||= {}
      session[SessionKey]['date_from'] = timesheet.date_from
      session[SessionKey]['date_to'] = timesheet.date_to
    end
  end
end
