let cache = {
	cacheKey: {
		// empty | loading | loaded
		state: 'empty',

		response: null,

		waitingForResponse: [],
	},
}

export const makeDeferred = () => {
	const deferred = {}

	deferred.promise = new Promise((resolve, reject) => {
		deferred.resolve = resolve
		deferred.reject = reject
	})

	return deferred
}

// Stable JSON serialization
// Props to: https://github.com/fraunhoferfokus/JSum
function makeCacheKey(obj) {
	if (Array.isArray(obj)) {
		return `[${obj.map((el) => makeCacheKey(el)).join(',')}]`
	} else if (typeof obj === 'object' && obj !== null) {
		let acc = ''
		const keys = Object.keys(obj).sort()
		acc += `{${JSON.stringify(keys)}`

		for (let i = 0; i < keys.length; i++) {
			acc += `${makeCacheKey(obj[keys[i]])},`
		}

		return `${acc}}`
	}

	return `${JSON.stringify(obj)}`
}

// Maybe this will be the definitive version of the cachedFetch function.
// We will have two strategies for handling concurrency:
//
// - abort previous requests and start a new one
// - queue requests and resolve them in order
//
// Also, don't deal anymore with cloning responses. This is a bad idea.
// Just resolve the json from the response once and cache it.
const cachedFetch = (url, body, options = {}) => {
	const cacheKey = makeCacheKey({
		...body,
		url,
	})

	if (!cache[cacheKey]) {
		cache[cacheKey] = {
			state: 'empty',
			response: null,
			waitingForResponse: [],
		}
	}

	if (cache[cacheKey].state === 'loaded') {
		const deferred = makeDeferred()

		deferred.resolve(cache[cacheKey].response.clone())

		return deferred.promise
	}

	if (cache[cacheKey].state === 'loading') {
		const deferred = makeDeferred()

		cache[cacheKey].waitingForResponse.push(deferred)

		return deferred.promise
	}

	// This is the first that triggered the fetch for that particular
	// cache key. If any other request comes in while this is loading,
	// we will add it to the waitingForResponse array and resolve it
	// once the request is done.
	if (cache[cacheKey].state === 'empty') {
		cache[cacheKey].state = 'loading'

		const deferred = makeDeferred()

		const options = {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},

			...options,
		}

		if (options.method === 'POST') {
			options.body = JSON.stringify(body)
		}

		fetch(url, options)
			.then((response) => {
				cache[cacheKey].response = response.clone()
				;[deferred, ...cache[cacheKey].waitingForResponse].forEach(
					(deferred) => {
						deferred.resolve(cache[cacheKey].response.clone())
					}
				)

				cache[cacheKey].waitingForResponse = []
				cache[cacheKey].state = 'loaded'
			})
			.catch((error) => {
				;[deferred, ...cache[cacheKey].waitingForResponse].forEach(
					(deferred) => {
						deferred.reject(cache[cacheKey].response)
					}
				)

				cache[cacheKey].waitingForResponse = []

				// Reset the cache entry so that we do fresh request next time
				// when a networking error occurs.
				cache[cacheKey].state = 'empty'
			})

		return deferred.promise
	}

	throw new Error('Invalid state', { cacheEntry: cache[cacheKey] })
}

export default cachedFetch
