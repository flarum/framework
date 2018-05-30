if ! [ -x "$(command -v yamllinter)" ]; then
  echo 'Error: yamllinter is not installed.' >&2
  exit 1
fi

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

cd "./locale"

RC=0

for r in *.yml
do
  [ "$r" != "validation.yml" ] || continue

  echo "Checking $r:"

  yamllinter --file "$r" --level 2

  if [ $? -eq 1 ]; then
    RC=1
    printf "${RED}⨉ faild${NC}\n"
  else
    printf "${GREEN}✓ passed${NC}\n"
  fi

  echo
done

exit $RC
