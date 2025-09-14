document.getElementById('input-id').addEventListener('input', function(e) {
	const input = e.target.value.trim();
	const outputElement = document.getElementById('input-url');
	
	if (!input) {
		outputElement.value = '';
		return;
	}

	// 1. Codeforces 常规题目 (cf1a 或 1a)
	if (input.startsWith('cf') || /^\d{1,5}[a-zA-Z]\d{0,1}$/.test(input)) {
		let problemId = input.startsWith('cf') ? input.substring(2) : input;
		
		const cfMatch = problemId.match(/^(\d{1,5})([a-zA-Z]\d{0,1})$/);
		if (cfMatch) {
			const contestId = cfMatch[1];
			const problemLetter = cfMatch[2].toUpperCase(); // 注意：大写字母
			outputElement.value = `https://codeforces.com/problemset/problem/${contestId}/${problemLetter}`;
			return;
		}
	}

	// 2. AtCoder 格式 (abc/arc/agc + 数字 + 字母)
	const atcoderMatch = input.match(/^(a[bgc]r?c)(\d+)([a-zA-Z])$/i);
	if (atcoderMatch) {
		const contestType = atcoderMatch[1].toLowerCase();
		const contestNumber = atcoderMatch[2];
		const problemLetter = atcoderMatch[3].toLowerCase();
		const contestId = contestType + contestNumber;
		
		outputElement.value = `https://atcoder.jp/contests/${contestId}/tasks/${contestId}_${problemLetter}`;
		return;
	}

	// 3. UOJ 格式 (uoj78)
	const uojMatch = input.match(/^uoj(\d+)$/i);
	if (uojMatch) {
		const problemId = uojMatch[1];
		outputElement.value = `https://uoj.ac/problem/${problemId}`;
		return;
	}

	// 4. QOJ 格式 (qoj789)
	const qojMatch = input.match(/^qoj(\d+)$/i);
	if (qojMatch) {
		const problemId = qojMatch[1];
		outputElement.value = `https://qoj.ac/problem/${problemId}`;
		return;
	}

	// 5. LOJ 格式 (loj456)
	const lojMatch = input.match(/^loj(\d+)$/i);
	if (lojMatch) {
		const problemId = lojMatch[1];
		outputElement.value = `https://loj.ac/p/${problemId}`;
		return;
	}

	// 6. SOJ 格式 (soj567) - 相对路径
	const sojMatch = input.match(/^soj(\d+)$/i);
	if (sojMatch) {
		const problemId = sojMatch[1];
		outputElement.value = `/problem/${problemId}`;
		return;
	}

	// 7. 洛谷格式 (lg1001 或 p1001)
	const lgMatch = input.match(/^(?:lg|p)(\d+)$/i);
	if (lgMatch) {
		const problemId = lgMatch[1];
		outputElement.value = `https://www.luogu.com.cn/problem/P${problemId}`;
		return;
	}

	// 8. Codeforces Gym 题目 (cf123456a 或 123456a，6位以上数字)
	if (input.startsWith('cf') || /^\d{6,}[a-zA-Z]$/.test(input)) {
		let problemId = input.startsWith('cf') ? input.substring(2) : input;
		
		const cfGymMatch = problemId.match(/^(\d{6,})([a-zA-Z])$/);
		if (cfGymMatch) {
			const contestId = cfGymMatch[1];
			const problemLetter = cfGymMatch[2].toUpperCase(); // 注意：大写字母
			outputElement.value = `https://codeforces.com/problemset/gymProblem/${contestId}/${problemLetter}`;
			return;
		}
	}

	// 如果不是已知格式，不改变输出
	// outputElement.value = '';
});
