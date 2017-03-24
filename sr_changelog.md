


# SR-Testlink Changelog

**Simple Report For Testlink Changelog**



# Release: 3.2 - 2017-03-24


### Summary

* Improve result table display

### Details

* **CSS**
	* Cleaning and update
* **PHP/HTML**
	* Improve result table display



# Release: 3.1 - 2017-02-10


### Summary

* Modify the ``FORM``

### Details

* **CSS**
	* Add new ``li`` class for the ``FORM``
* **PHP/HTML**
	* Display "filter" on the test plans ``FORM`` based on its first letter



# Release: 3.0 - 2017-01-11


### Summary

* Select several test plans is now possible
* Modify the behavior of the coverage button
* Known bug: After build selection, change test plan can lead to wrong result,
  use the "reset" button in this case
* Rename the project

### Details

* **JAVASCRIPT**
	* Cleaning
* **PHP/HTML**
	* Coverage column disappears
	* Coverage doesn't show obsolete testcases or never executed testcases
* **SQL**
	* Refactoring everything to take into account the multi-test plans request
	* Simplify the request on build table and testsuite table
	* New view (sr_vw_test plans)
	* Ignore testsuite starting with an ``_``



# Release: 2.3 - Date 2017-01-03


### Summary

* Sort test plans list by name in the form


### Details

* **CSS**
	* Split **CSS** file
* **SQL**
	* Add "ORDER BY" in test plan request



# Release: 2.2 - Date 2015-11-02


### Summary

* Fix test condition on unset variables

### Details

* **PHP/HTML**
	* Replace ``if($_GET['xxxx'])`` by ``if(isset($_GET['xxxx']))`` to test
	  unset variables



# Release: 2.1 - Date 2015-10-08


### Summary

* Add a legend


### Details

* **CSS**
	* Add changelog CSS
	* Improve CSS for ``sr_tools.php``
* **PHP/HTML**
	* By default the checkbox ``low level testsuite`` is checked
	* Add a legend
	* Modify executed percent display



# Release: 2.0 - Date 2015-10-06


### Summary

* Consult without login
* General display improvement


### Details

* **CSS**
	* New style for the button
* **PHP/HTML**
	* Save the state of ``low level testsuite`` checkbox
	* Allow anyone to consult the statistics
	* Hide misleading ``total`` row
	* Move the reset link (form reset)
	* Add a changelog page (click on version X.Y)



# Release: 1.2 - Date 2015-06-22


### Summary

* Add pie chart
* Add "hide 100% passed"


### Details

* **CSS**
	* Update the **CSS**
* **JAVASCRIPT**
	* Add chart.js
	* Add new function to draw the pie chart
* **PHP/HTML**
	* Add "hide 100% passed"
	* Add the percentage of passed.
	* Modify the FORM
* **SQL**
	* Fix bugs



# Release: 1.1 - Date 2015-06-10


### Summary

* Add button to hide low level testsuite
* Change the layout
* Add **CSS**


### Details

* **CSS**
	* Add **CSS**
* **JAVASCRIPT**
	* Add new toggle function
* **PHP/HTML**
	* Modify the FORM
* **SQL**
	* Keep the testsuite level (in the tree view)



# Release: 1.0 - Date 2015-06-10


### Summary

* Beta version

### Details





