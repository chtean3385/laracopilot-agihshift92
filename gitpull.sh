#!/bin/bash
find .git -name "*.lock" -type f -delete 2>/dev/null
git pull
