#!/bin/bash
find .git -name "*.lock" -delete 2>/dev/null
git pull "$@"
