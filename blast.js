// UI handling


function setupPage(firstLoad)
{
    // Run on load, hide everything we don't want js enabled users to see

    // Hide what we show for non javascript users
    $(".hspsummary,.hspcompare").hide();

    if (firstLoad)
	{
	    $("#advancedform").hide()
	    // Make sure the "Advanced options" arrow looks right.
	    $("#advancedarrow").html("&#x25ba;");
	    	}

    // Show javascript objects
    $(".jsonly").show();

    // Show the right databases for the selected program.
    formChange();
}

function toggle(id,ref,texthidden,textvisible)
{
    // Toggle objects selected by id, optionally, change text of ref
    // to approiate (after state change).

    $(id).toggle();
    $("#line").attr("style").left=0;

    if (ref != null)
	{	    
	    if ($(id).is(":visible"))
		{
		    $(ref).html(textvisible);
		}
	    else
		$(ref).html(texthidden);
	}
}

// Show the right databases to choose from

function formChange()
{

    // Fix the right selectable values
    $.getJSON("blastxml.php?js=1&op=dbs&program=" + $("#program").val(),
        function(data){
	    // Verify the dbs we got are for this program
	    if (data.program == $("#program").val())
	    {
		var optionscollection = $("#db").attr("options");

		// Remove old options
		optionscollection.length=0

		for (var i=0; i<data.dbs.length; i++)
		    {
			var dboption = new Option(
			    data.dbs[i], //data.dbs[i],
			    data.dbs[i], //		    
			    false,
			    false);

			optionscollection[i] = dboption;
		    }
	    }
	    else
		alert("Something weird happened while building the page!");

        });
}



function queryDef()
{
    var s = $("#querysequence").val()
    
    if( s[0] == '>' &&                // We have a definition header and a newline
	(s.indexOf("\n") != -1))
    {
	// Return the first line, minus the starting >
	return s.substring(1,
			   s.indexOf("\n"));

    }


    return "unnamed";
}






// Submit and result loading

function loadingText(s)
{
    var numdots = s.split(".").length % 7;
    var dots = Array( numdots + 1 ).join( '.' );
    return "<span class='loading'>Loading" + dots + "</span>";
}


function checkJob(info)
{
    // Update loading message.
    $("#resultset").html(loadingText(
	$("#resultset").html()));

    // Remove old timer - we don't want to fire multiple times
    window.clearInterval(info.intervalId);
    
    // Do a query to see if the job is done yet.
    $.get("blastxml.php?js=1&op=loaded&id=" + info.id, 
	  null,
	  function (data,status) 
	  {
	      // Lambda function for parameter passing
	      checkLoaded(info,data,status);
	  }
	 );

}

function checkLoaded(info,data,status)
{
    if (status != 'success')
	{
	    alert( 'Sorry, problem loading results!');
	    return false;
	}

    if (data != '1')
	{
	    // Still waiting for job to complete
	    info.intervalId = window.setInterval(function () 
						 {
						     checkJob(info);
						 },
						 3000);
	    return;
	}
    else
	{
	    // Job done - load result page.
	    $("#resultset").load("blastxml.php",
				 {'js':'1', 
				  'id':info.id, 
				  'op':'render'
				 },
				 function (text, status, request)
				 {
				     // Lambda function to make sure we see what we want 
				     setupPage(false);
				     
				     // Fix up the title;
				     document.title = document.title.toString().replace("searching",
											"results for");
				 }
				);
	    
	}
}



function submitCallback(data,status)
{
    if (status != 'success')
	{
	    alert('Sorry, there seems to be some issue with this doohickey. Please try later!');
	    return;
	}
    

    $("#resultset").html(loadingText(''));
    var info = new Object;
    info.id = data;

    info.intervalId = window.setInterval(function () 
					 {
					     checkJob(info);
					 },
					 3000);
    
}




function verifyForm()
{
    var problems=''

    if (!$("#querysequence").val() && !$("#queryfile").val())
    {
	problems += "\n\nNo query sequence given!";
	
    }

    if (problems.length > 0)
	{
	    alert("Found the following problems:"+problems);
	    return false;
	}

    return true;
}


function submitForm()
{

    if (!verifyForm())
	return false;

    var data=$("#blastform").serialize();
    $("#resultset").html(loadingText(''));

    document.title = document.title.toString() + ' - searching ' + queryDef() ;

    $.post("blastxml.php?js=1&op=submit",data, submitCallback);
    
    // Inhibit normal submit, we use ajax.
    return false;    
}

