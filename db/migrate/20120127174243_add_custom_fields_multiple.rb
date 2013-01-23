class AddCustomFieldsMultiple < ActiveRecord::Migration
  def up
    add_column :custom_fields, :multiple, :boolean, :default => false
  end

  def down
    remove_column :custom_fields, :multiple
  end
end
