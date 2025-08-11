function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}


function timeElapsedString(pubDateStr) {
	const past = new Date(pubDateStr).getTime();

    const now = Date.now();

    if (isNaN(past)) return 'Unknown time';

    let etime = Math.floor((now - past) / 1000); // time difference in seconds

    if (etime < 1) return 'just now';

    const intervals = {
        year: 365 * 24 * 60 * 60,
        month: 30 * 24 * 60 * 60,
        day: 24 * 60 * 60,
        hour: 60 * 60,
        minute: 60,
        second: 1
    };

    for (const [label, seconds] of Object.entries(intervals)) {
    	const d = etime / seconds;
        if (d >= 1) {
        	const r = Math.round(d);
            return `${r} ${label}${r > 1 ? 's' : ''} ago`;
        }
	}
}