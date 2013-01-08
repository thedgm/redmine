module PersonalTimesheetHelper
  def showing_users(users)
    l(:timesheet_showing_users) + users.collect(&:name).join(', ')
  end

  def permalink_to_timesheet(timesheet)
    link_to(l(:timesheet_permalink),
            :controller => 'personal_timesheet',
            :action => 'report',
            :timesheet => {
              :projects => timesheet.projects.collect(&:id),
              :date_from => timesheet.date_from,
              :date_to => timesheet.date_to,
              :activities => timesheet.activities,
              :users => timesheet.users,
              :sort => timesheet.sort
            })
  end

 
  def new_all_time(arr, from, to, checked)
    to = to.to_date
    from = from.to_date
    if checked
    while to >= from 
      if !arr.include?(from)
        arr << from
      end
      from = from +1
    end
    else
      while to >= from
      if !arr.include?(from)
        arr << from
      end
      begin
        if from.wday == 0 || from.wday == 6
          arr.delete(from)
        end
      end
      from = from +1
    end
    end
  arr.sort! { |a,b| a <=> b }
  arr.uniq
  end
  

#  def get_issue_num_for_date_project(date, prj)
#    entries_for_date = TimeEntry.find(:all, :conditions =>["spent_on =? and user_id = ? and project_id = ? and activity_id IN (?)",
#       date, User.current.id, prj.id, act ])
#  end
 def get_personal_projects_for_data(date, user)
   if !User.current.admin?
     user = User.current
   end
   entries_for_date = TimeEntry.find(:all, :conditions =>["spent_on =? and user_id = ?", date, user.id ])
   pr_id = []
   entries_for_date.each { |item|  
     pr_id << item.project_id
   }
   projects = Project.find(:all, :conditions => ['id IN (?)', pr_id])
   projects
 end

 def get_hours_per_day_prj(day, user, projects)
   total = 0
   logs = TimeEntry.find(:all, :conditions =>["spent_on =? and user_id = ? and project_id IN (?)",
       day, user.id, projects ])
   logs.each { |item| total += item.hours }
   total
 end

 def get_hours_per_day_project(day, prj, user)
   if !User.current.admin?
     user = User.current
   end
   total = 0
   logs = TimeEntry.find(:all, :conditions =>["spent_on =? and user_id = ? and project_id = ?",
       day, user.id, prj.id ])
   logs.each { |item| total += item.hours }
   total
 end
 
 def get_hours_per_day(day, user)
   if !User.current.admin?
     user = User.current
   end
   total = 0
   entries_for_date = TimeEntry.find(:all, :conditions =>["spent_on =? and user_id = ?", day, user.id ])
   entries_for_date.each { |item|
     total += item.hours
   }
   total
 end

 def grand_total(from, to, user, projects, act)
   total = 0
   entries_for_date = TimeEntry.find(:all,
     :conditions =>["spent_on >= ? and spent_on <=? and user_id = ? and 
            project_id IN (?) and activity_id IN (?)", from, to, user.id, projects, act ])
   entries_for_date.each { |item|
     total += item.hours
   }
   total
 end

def get_entries_per_day_project_act(day, prj,act, user)
  if !User.current.admin?
     user = User.current
   end
   total = 0
   entries_for_date = TimeEntry.find(:all, :conditions =>["spent_on =? and user_id = ? and project_id = ? and activity_id IN (?)",
       day, user.id, prj, act ])
   entries_for_date.each { |item|
     total += item.hours
   }
#   total
    entries_for_date
 end
  def get_entries_per_day_project(day, prj, user)
    if !User.current.admin?
     user = User.current
   end
   total = 0
   entries_for_date = TimeEntry.find(:all, :conditions =>["spent_on =? and user_id = ? and project_id = ?",
       day, user.id, prj ])
   entries_for_date.each { |item|
     total += item.hours
   }
#   total
entries_for_date
 end

  def toggle_issue_arrows(issue_id)
    js = "toggleTimeEntries('#{issue_id}'); return false;"

    return toggle_issue_arrow(issue_id, 'toggle-arrow-closed.gif', js, false) +
      toggle_issue_arrow(issue_id, 'toggle-arrow-open.gif', js, true)
  end

  def toggle_issue_arrow(issue_id, image, js, hide)
    style = "display:none;" if hide
    style ||= ''

    content_tag(:span,
                link_to_function(image_tag(image, :plugin => "redmine_personal_timesheet"), js),
                :class => "toggle-" + issue_id.to_s,
                :style => style
                )

  end
 
  def displayed_time_entries_for_issue(time_entries)
    time_entries.collect(&:hours).sum
  end

  def color_for_report(elem, user)
     current_hours = number_with_precision(get_hours_per_day(elem, user),  :Precision => @precision).to_i
    color = ""
    if 0 < current_hours && current_hours < 8
      color = "#FDBF3B"
    end
    if current_hours > 8
      color = "lime"
    end
    if current_hours == 0
      color = "red"
    end
    if current_hours == 8
      color = "#dadada"
    end
    if elem.wday == 0
      color = "magenta"
    end
   color
  end
end
