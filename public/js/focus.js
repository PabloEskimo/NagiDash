function init() {
	if(document.getElementsByTagName('input')[0].focus()){
		return true;
	};
}
window.onload = init;