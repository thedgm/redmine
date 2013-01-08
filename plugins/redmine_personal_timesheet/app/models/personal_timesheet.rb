class PersonalTimesheet
  attr_accessor :date_from, :date_to, :projects, :activities, :users, :allowed_projects

  # Time entries on the Timesheet in the form of:
  #   project.name => {:logs => [time entries], :users => [users shown in logs] }
  #   project.name => {:logs => [time entries], :users => [users shown in logs] }
  # project.name could be the parent project name also
  attr_accessor :time_entries

  # Sort time entries by this field
  attr_accessor :sort

  ValidSortOptions = {
    :project => 'Project',
    :user => 'User',
    :issue => 'Issue',
	:issuePR => 'User-Project-Issue',
	:projectUser=> 'Project-User-Issue'
  }

  def initialize(options = { })

    self.projects = [ ]
    self.time_entries = options[:time_entries] || { }
    self.allowed_projects = options[:allowed_projects] || [ ]

    unless options[:activities].nil?
      self.activities = options[:activities].collect { |a| a.to_i }
    else
#      self.activities =  Enumeration::get_values('ACTI').collect(&:id)
      self.activities =  TimeEntryActivity.all.collect { |a| a.id.to_i }
    end


    unless options[:users].nil?
      self.users = options[:users].collect { |u| u.to_i }
    else
      if User.current.admin?
        self.users = User.find(:all, :conditions => ["id = ?",
            PersonalTimesheet::MYREQUESTEDUSER]).collect(&:id)
      else
        self.users = User.find(:all, :conditions => ["id = ?", User.current.id]).collect(&:id)
      end
      
    end

    if !options[:sort].nil? && options[:sort].respond_to?(:to_sym) && ValidSortOptions.keys.include?(options[:sort].to_sym)
      self.sort = options[:sort].to_sym
    else
      self.sort = :project
    end

    beforeAmonth = Date.new(Date.today.year, Date.today.month, 1)
    self.date_from = options[:date_from] || beforeAmonth.to_s
    self.date_to = options[:date_to] || Date.today.to_s
  end

  def self.set(value)
    const_set("MYREQUESTEDUSER", value)
  end

  # Gets all the time_entries for all the projects
  def fetch_time_entries
    self.time_entries = { }
    fetch_time_entries_by_project
  end

  protected

  def conditions(users)
    conditions = ['spent_on >= (?) AND spent_on <= (?) AND activity_id IN (?) AND user_id IN (?)',
                  self.date_from, self.date_to, self.activities, users ]

    Redmine::Hook.call_hook(:plugin_timesheet_model_timesheet_conditions, { :timesheet => self, :conditions => conditions})
    return conditions
  end

  private


  def time_entries_for_all_users(project)
    return project.time_entries.find(:all,
                                     :conditions => self.conditions(self.users),
                                     :include => [:activity, :user, {:issue => [:tracker, :assigned_to, :priority]}],
                                     :order => "spent_on ASC")
  end

  def time_entries_for_current_user(project)
    return project.time_entries.find(:all,
                                     :conditions => self.conditions(User.current.id),
                                     :include => [:activity, :user, {:issue => [:tracker, :assigned_to, :priority]}],
                                     :order => "spent_on ASC")
  end

  
  def issue_time_entries_for_all_users(issue)
    return issue.time_entries.find(:all,
                                   :conditions => self.conditions(self.users),
                                   :include => [:activity, :user],
                                   :order => "spent_on ASC")
  end

  def issue_time_entries_for_current_user(issue)
    return issue.time_entries.find(:all,
                                   :conditions => self.conditions(User.current.id),
                                   :include => [:activity, :user],
                                   :order => "spent_on ASC")
  end

  def time_entries_for_user(user)
    return TimeEntry.find(:all,
                          :conditions => self.conditions([user]),
                          :include => [:activity, :user, {:issue => [:tracker, :assigned_to, :priority]}],
                          :order => "spent_on ASC"
                          )
  end

  def fetch_time_entries_by_project
    self.projects.each do |project|
      logs = []
      users = []
      if User.current.admin?
        # Administrators can see all time entries
       
        logs = time_entries_for_all_users(project)
        users = logs.collect(&:user).uniq.sort
      elsif User.current.allowed_to?(:see_project_timesheets, project)
     
        # Users with the Role and correct permission can see all time entries
        logs = time_entries_for_all_users(project)
        users = logs.collect(&:user).uniq.sort
      elsif User.current.allowed_to?(:view_time_entries, project)
        # Users with permission to see their time entries
        logs = time_entries_for_current_user(project)
        users = logs.collect(&:user).uniq.sort
      else
        # Rest can see nothing
      end

      # Append the parent project name
      if project.parent.nil?
        unless logs.empty?
          self.time_entries[project.name] = { :logs => logs, :users => users }
        end
      else
        unless logs.empty?
          self.time_entries[project.parent.name + ' / ' + project.name] = { :logs => logs, :users => users }
        end
      end
    end
  end

  def project_for_all_usersPR(user_id)
    user = User.find_by_id(user_id)
    return user.projects.find(:all,
							  :order => "name ASC")
  end

  def issues_for_project(project)
    return project.issues.find(:all,
							  :include => [:tracker, :assigned_to, :priority],
							  :order => "subject ASC")
  end



  def time_entries_for_issues(issue,user_ID)
	return issue.time_entries.find(:all,
								   :conditions => self.conditions(user_ID),
								   :include => [:activity, :user, :project, {:issue => [:tracker, :assigned_to, :priority]}],
                                   :order => "spent_on ASC")
  end

  def time_entries_for_issues1(issue,user_ID)
	return TimeEntry.find(:all,
								   :conditions => self.conditions(user_ID),
								   :conditions =>"issue_id = " + issue.to_s + " and user_id = " + user_ID.to_s,
								   :include => [:activity, :user, :project, {:issue => [:tracker, :assigned_to, :priority]}],
                                   :order => "spent_on ASC")
  end


  def alltime_entries_for_issues(issue)
	return issue.time_entries.find( :all, :include => [:issue] )
  end

   def alltime_entries_for_issuesID(issueID)
	return TimeEntry.find( :all,
									:conditions =>"issue_id = " + issueID.to_s,
									:include => [:activity, :user, {:issue => [:tracker, :assigned_to, :priority]}],
									:joins => "Left JOIN issues  ON issues.id = issue_id"	 )
  end


  def time_entries_for_all_usersIssue(project,userID)
    return project.time_entries.find( :all,  :select => " time_entries.*, issues.id, issues.subject, issues.estimated_hours,sum(time_entries.hours",
										:include => [:issue],
										:conditions => self.conditions(userID),
										:group => "issue_id")
  end
end