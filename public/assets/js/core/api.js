/**
 * Client Cache & In-Flight Deduplication
 */
export const ApiCache = {
    store: new Map(),
    inFlight: new Map(),
    defaultTtl: 60000,

    get(key) {
        const item = this.store.get(key);
        if (!item) return null;
        if (Date.now() > item.expiresAt) {
            this.store.delete(key);
            return null;
        }
        return item.data;
    },

    set(key, data, ttl = this.defaultTtl) {
        this.store.set(key, {
            data,
            expiresAt: Date.now() + ttl,
            storedAt: Date.now()
        });
    },

    invalidate(tag) {
        for (const key of this.store.keys()) {
            if (key.startsWith(tag)) {
                this.store.delete(key);
            }
        }
    },

    clear() {
        this.store.clear();
        this.inFlight.clear();
    }
};

/**
 * Fetch wrapper with cache & JSON auto-parse
 */
export const api = {
    async request(url, options = {}, { cache = false, ttl = 60000, force = false } = {}) {
        const cacheKey = `${options.method || 'GET'}:${url}`;

        if (cache && !force) {
            const cachedData = ApiCache.get(cacheKey);
            if (cachedData !== null) return cachedData;
        }

        if (ApiCache.inFlight.has(cacheKey)) {
            return ApiCache.inFlight.get(cacheKey);
        }

        const fetchPromise = (async () => {
            const headers = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {})
            };

            try {
                const response = await fetch(url, { ...options, headers });
                const result = await response.json();

                if (!response.ok) {
                    if (response.status === 401) {
                        window.location.hash = '#/login';
                    }
                    const error = new Error(result.message || 'Request failed');
                    error.status = response.status;
                    error.errors = result.errors;
                    throw error;
                }

                if (cache) {
                    ApiCache.set(cacheKey, result, ttl);
                }

                return result;
            } finally {
                ApiCache.inFlight.delete(cacheKey);
            }
        })();

        ApiCache.inFlight.set(cacheKey, fetchPromise);
        return fetchPromise;
    },

    get(url, config = {}) {
        return this.request(url, { method: 'GET' }, config);
    },

    post(url, body = {}) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },

    put(url, body = {}) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    },

    delete(url) {
        return this.request(url, { method: 'DELETE' });
    }
};
