<?php
/**
 * Download Manager manage plugin language
 */

// Success messages
$lang['AdminMain.!success.category_added'] = "The category has been successfully created.";
$lang['AdminMain.!success.category_updated'] = "The category has been successfully updated.";
$lang['AdminMain.!success.category_deleted'] = "The category has been successfully deleted.";
$lang['AdminMain.!success.file_added'] = "The file has been successfully added.";
$lang['AdminMain.!success.file_updated'] = "The file has been successfully updated.";
$lang['AdminMain.!success.file_deleted'] = "The file has been successfully deleted.";
$lang['AdminMain.!success.url_deleted'] = "The static URL has been successfully deleted.";
$lang['AdminMain.!success.url_added'] = 'The static URL has been successfully added.';
$lang['AdminMain.!success.url_updated'] = 'The static URL has been successfully updated.';


// Tooltips
$lang['AdminMain.!tooltip.path_to_file'] = "Enter the absolute path to the file on the file system.";
$lang['AdminMain.!tooltip.category_type'] = "The URL will point to the last file uploaded to this category.";


// Text
$lang['AdminMain.!text.root_directory'] = "Home Directory";
$lang['AdminMain.!text.open_parenthesis'] = "(";
$lang['AdminMain.!text.forward_slash'] = "/";
$lang['AdminMain.!text.closed_parenthesis'] = ")";


// Modal
$lang['AdminMain.modal.delete_file'] = "Are you sure you want to delete this file?";
$lang['AdminMain.modal.delete_category'] = "Are you sure you want to delete this category? All subcategories and files within this category will be moved to the parent category.";
$lang['AdminMain.modal.delete_url'] = 'Are you sure you want to delete this static URL?';


// Files
$lang['AdminMain.files.page_title'] = "Download Manager > Manage";
$lang['AdminMain.files.boxtitle_downloadmanager'] = "Download Manager";

$lang['AdminMain.files.tab_files'] = 'Files';
$lang['AdminMain.files.tab_urls'] = 'Static URLs';

$lang['AdminMain.files.add_download'] = "Add Download Here";
$lang['AdminMain.files.add_category'] = "Add Category Here";

$lang['AdminMain.files.go_back'] = "Go up a level";

$lang['AdminMain.files.edit'] = "Edit";
$lang['AdminMain.files.delete'] = "Delete";

$lang['AdminMain.files.no_downloads'] = "There are no downloads in this section.";


// URLs
$lang['AdminMain.urls.page_title'] = "Download Manager > Manage";
$lang['AdminMain.urls.boxtitle_downloadmanager'] = "Download Manager";

$lang['AdminMain.urls.add_url'] = 'Add Static URL';

$lang['AdminMain.urls.tab_files'] = 'Files';
$lang['AdminMain.urls.tab_urls'] = 'Static URLs';

$lang['AdminMain.urls.heading_url'] = 'URL';
$lang['AdminMain.urls.heading_file'] = 'File';
$lang['AdminMain.urls.heading_link'] = 'Link';
$lang['AdminMain.urls.heading_options'] = 'Options';

$lang['AdminMain.urls.edit'] = 'Edit';
$lang['AdminMain.urls.delete'] = 'Delete';
$lang['AdminMain.urls.latest_file'] = 'Latest file from <strong>%1$s</strong>'; // %1$s is the name of the category
$lang['AdminMain.urls.no_urls'] = 'There are no Static URLs in this section.';

// Add download
$lang['AdminMain.add.page_title'] = "Download Manager > Add Download";

$lang['AdminMain.add.boxtitle_root'] = "Add Download to the %1\$s"; // %1$s is the name of the root directory
$lang['AdminMain.add.boxtitle_add'] = "Add Download to Category [%1\$s]"; // %1$s is the name of the category the download is to be uploaded to

$lang['AdminMain.add.field_public'] = "Publicly Available";
$lang['AdminMain.add.field_logged_in'] = "Must be logged in";
$lang['AdminMain.add.field_name'] = "Name";
$lang['AdminMain.add.field_available_to_client_groups'] = "Available to Client Groups";
$lang['AdminMain.add.field_available_to_packages'] = "Available to Packages";
$lang['AdminMain.add.text_clientgroups'] = "Selected Client Groups";
$lang['AdminMain.add.text_packagegroups'] = "Selected Packages";
$lang['AdminMain.add.text_availableclientgroups'] = "Available Client Groups";
$lang['AdminMain.add.text_availablepackages'] = "Available Packages";
$lang['AdminMain.add.field_upload'] = "Upload File";
$lang['AdminMain.add.field_path'] = "Specify Path to File";
$lang['AdminMain.add.field_file'] = "File";
$lang['AdminMain.add.field_file_name'] = "Path to File";

$lang['AdminMain.add.submit_add'] = "Add Download";
$lang['AdminMain.add.submit_cancel'] = "Cancel";


// Add URL
$lang['AdminMain.addurl.page_title'] = "Download Manager > Add Static URL";

$lang['AdminMain.addurl.boxtitle_add'] = 'Add Static URL';
$lang['AdminMain.addurl.field_url'] = 'URL Name';
$lang['AdminMain.addurl.field_file'] = 'File';
$lang['AdminMain.addurl.field_category'] = 'Category';
$lang['AdminMain.addurl.submit_add'] = 'Add URL';
$lang['AdminMain.addurl.submit_cancel'] = 'Cancel';


// Edit download
$lang['AdminMain.edit.page_title'] = "Download Manager > Add Download";

$lang['AdminMain.edit.boxtitle_edit'] = "Update Download";

$lang['AdminMain.edit.field_public'] = "Publicly Available";
$lang['AdminMain.edit.field_logged_in'] = "Must be logged in";
$lang['AdminMain.edit.field_name'] = "Name";
$lang['AdminMain.edit.field_available_to_client_groups'] = "Available to Client Groups";
$lang['AdminMain.edit.field_available_to_packages'] = "Available to Packages";
$lang['AdminMain.edit.text_clientgroups'] = "Selected Client Groups";
$lang['AdminMain.edit.text_packagegroups'] = "Selected Packages";
$lang['AdminMain.edit.text_availableclientgroups'] = "Available Client Groups";
$lang['AdminMain.edit.text_availablepackages'] = "Available Packages";
$lang['AdminMain.edit.field_upload'] = "Upload File";
$lang['AdminMain.edit.field_path'] = "Specify Path to File";
$lang['AdminMain.edit.field_file'] = "File";
$lang['AdminMain.edit.field_file_name'] = "Path to File";

$lang['AdminMain.edit.submit_edit'] = "Update Download";
$lang['AdminMain.edit.submit_cancel'] = "Cancel";


// Edit URL
$lang['AdminMain.editurl.page_title'] = "Download Manager > Edit Static URL";

$lang['AdminMain.editurl.boxtitle_add'] = 'Edit Static URL';
$lang['AdminMain.editurl.field_url'] = 'URL Name';
$lang['AdminMain.editurl.field_file'] = 'File';
$lang['AdminMain.editurl.field_category'] = 'Category';
$lang['AdminMain.editurl.submit_add'] = 'Edit URL';
$lang['AdminMain.editurl.submit_cancel'] = 'Cancel';


// Add category
$lang['AdminMain.addcategory.page_title'] = "Download Manager > Add Category";

$lang['AdminMain.addcategory.boxtitle_root'] = "Add Category to the %1\$s"; // %1$s is the name of the root directory
$lang['AdminMain.addcategory.boxtitle_addcategory'] = "Add Category to Category [%1\$s]"; // %1$s is the name of the category that this category is to be nested under

$lang['AdminMain.addcategory.field_name'] = "Name";
$lang['AdminMain.addcategory.field_description'] = "Description";

$lang['AdminMain.addcategory.submit_add'] = "Create Category";
$lang['AdminMain.addcategory.submit_cancel'] = "Cancel";


// Edit category
$lang['AdminMain.editcategory.page_title'] = "Download Manager > Update Category";

$lang['AdminMain.editcategory.boxtitle_editcategory'] = "Update Category [%1\$s]"; // %1$s is the name of the category

$lang['AdminMain.editcategory.field_name'] = "Name";
$lang['AdminMain.editcategory.field_description'] = "Description";

$lang['AdminMain.editcategory.submit_edit'] = "Update Category";
$lang['AdminMain.editcategory.submit_cancel'] = "Cancel";

// Package names
$lang['AdminMain.package_name'] = '%1$s (%2$s)'; // %1$s is the package name, %2$s is the package status
