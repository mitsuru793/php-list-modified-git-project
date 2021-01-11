# List modified git project

## Get Started

```
> ls -1
project-dir1
project-dir2

> list-project-sorting--by-modified .
1 project-dir1 2018-07-22
2 project-dir2 2019-02-09
```

## Sort

This sorts project directories by their modified.

The modified is decided by the following:

1. uncommit file (also if project is not git repository)
1. author date of last commit