import collections
p="resources/views/documents/show.blade.php"
s=open(p,encoding="utf-8").read()
c=collections.Counter(ch for ch in s if ord(ch)>127)
for ch,n in c.items():
    print(repr(ch), hex(ord(ch)), n)
PY