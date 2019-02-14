var body = document.getElementsByTagName("body")[0];

function toggleForm(formid) {
	let forms = document.getElementsByClassName('form');
	for (i=0;i<forms.length;i++) {
		forms[i].style.display = "none";
	};
	let targetForm = document.getElementById(formid);
	targetForm.style.display = "block";
	let title = document.getElementById('title');
	if (formid == 'loginform') {
		title.innerHTML = 'Login - Chore Champs';
	} else if (formid == 'signupform') {
		title.innerHTML = 'Sign Up - Chore Champs';
	} else {
		console.log('Invalid target form, failed to switch');
	}
};

function redirect(page) {
	window.location = page;
	if (page == "dashboard.php#section-a") {
		document.getElementById('code-input').focus();
	}
}

if (document.body.contains(document.getElementById("errorbanner"))) {
	body.onload = setTimeout(function() {
	let errorbanner = document.getElementById("errorbanner");
	errorbanner.style.height = "0px";
	errorbanner.style.padding = "0px";
	setTimeout(function() {
		errorbanner.style.display = "none";
	}, 100);
}, 4000);
};

if (document.body.contains(document.getElementById("jquery"))) {
	$("select").mousedown(function(e){
    e.preventDefault();
    
		var select = this;
    var scroll = select.scrollTop;
    
    e.target.selected = !e.target.selected;
    
    setTimeout(function(){select.scrollTop = scroll;}, 0);
    
    $(select).focus();
}).mousemove(function(e){e.preventDefault()});
};
