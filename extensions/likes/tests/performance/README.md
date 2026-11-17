# Like-counter deletion benchmark

After setting up the integration test database, run from `extensions/likes`:

```sh
../../../vendor/bin/phpunit -c tests/phpunit.integration.xml tests/performance/LikeCountBenchmarkTest.php --filter benchmark_deleting_a_popular_post
```

This test creates 3,000 users with 1,000 historical likes each, then measures
three full DELETE requests each for posts with 0, 1,000 and 3,000 likers.
It asserts the remaining counts and rolls its fixtures back. It lives outside
the normal integration suite because seeding three million likes is expensive.

Medians from three requests per case, PHP 8.5.12 on the local test bench:

| Database | 0 likers | 1,000 likers | 3,000 likers |
| --- | ---: | ---: | ---: |
| SQLite | 5.89 ms | 27.50 ms | 77.39 ms |
| MariaDB 10.11 | 23.23 ms | 43.79 ms | 277.47 ms |

These are local measurements, not directly comparable with another machine's
request timings. Deletion now aggregates only the deleted posts' contributions;
it does not recount each liker's historical likes.
