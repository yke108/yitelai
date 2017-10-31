function compareDate(left_time, right_time) {
	return ((new Date(left_time.replace(/-/g, "\/"))) <= (new Date(right_time.replace(/-/g, "\/"))));
}