'''
Created on Jun 1, 2010

@author: ola
'''
import tokenize,token,StringIO,math,sys,types

class Parse(object):
    '''
    classdocs
    '''
    def nextInt(self):
        next(self)
        return int(self.num)
    
    def int(self):
        return int(self.num)
    def double(self):
        return self.num
    def long(self):
        return long(self.num)
    def isLeftBracket(self):
        return self.token == "OP" and self.string == "["
    def isRightBracket(self):
        return self.token == "OP" and self.string == "]"
    def isComma(self):
        return self.token == "OP" and self.string == ","
    def isEnd(self):
        return self.token == "ENDMARKER" or self.token == "ERRORTOKEN"
    
    def stringize(self,str):
        if len(str) > 0 and str[0] == '"':
            return str[1:-1]
        return str

    def next(self):
        #print "entering next"
        try:
            toktype,toktext,(srow,scol), (erow,ecol), line = self.tokenizer.next()
            self.token = token.tok_name[toktype]
            if self.token in ["DEDENT", "INDENT", "NEWLINE"]:
                self.next()
            
            if self.token == "NUMBER":
                if toktext.find(".") != -1:
                    self.num = float(toktext)
                else:
                    self.num = long(toktext)
                self.string = toktext
            elif toktext == "-":
                toktype,toktext,(srow,scol), (erow,ecol), line = self.tokenizer.next()
                self.num = -1*float(toktext)
                self.string = "-" + toktext
            else:
                self.num = 1
                self.string = self.stringize(toktext)
#                str = self.string.strip()
#                if len(str) == 0:
#                    print "whitespace"
#                    self.next()
                
                
            if self.isComma():
                self.next()
                
            #print "parsed: "+self.string+" is '"+self.token+"'"
            
        except:
            #self.token="ENDMARKER"
            
            #print "exception on ",self.token,"'"+toktext+"'",len(toktext)
            raise
            
        
            
    def parseVS(self):
        list = []
        self.next()
        if self.isEnd():
            return None
        if not self.isLeftBracket():
            raise RuntimeError, "no left bracket in parseVS " + self.token + " " + self.string
        while True:
            self.next()
            if self.isRightBracket():
                break
            if self.isComma():
                continue
            #print "adding ",self.string
            list.append(self.string)
        return list
    
     
    def parseVI(self):
        list = []
        self.next()
        if self.isEnd():
            return None
        if not self.isLeftBracket():
            str = "no left bracket in parseVI " + self.token + " " + self.string
            raise RuntimeError, str
        while True:
            self.next()
            if self.isRightBracket():
                break
            if self.isComma():
                continue
            list.append(int(self.num))
        return list
    
    def parseVD(self):
        list = []
        self.next()
        if self.isEnd():
            return None
        if not self.isLeftBracket():
            raise RuntimeError, "no left bracket in parseVD " + self.token
        while True:
            self.next()
            if self.isRightBracket():
                break
            if self.isComma():
                continue
            list.append(float(self.num))
        return list

    def getLong(self):
        self.next()
        if self.isEnd():
            return None
        #print "about to return ",self.num
        return long(self.num)
            
    def getInt(self):
        self.next()
        if self.isEnd():
            return None
        #print "about to return ",self.num
        return int(self.num)
    
    def getDouble(self):
        self.next()
        if self.isEnd():
            return None
        return float(self.num)
    
    def getString(self):
        self.next()
        if self.isEnd():
            return None
        return self.string
    
    def ok(self):
        return self._ok
    
    def matchDouble(self,d):
        self._ok = False
        good = self.getDouble()
        dummy = self.getDouble()
	if d == None:
	    return good
#       print "matching ",d," to ", good
        if d == good:
            self._ok = True
            return good
        if good == 0:
		good = 0.0000000000001
#        self._ok = math.fabs((d-good)/good) < 10e-5
        self._ok = math.fabs(d-good)/good < 10e-3
        return good
    
    def matchInt(self,i):
        self._ok = False
        good = self.getInt()
        dummy = self.getInt()
        #print "\tmatching read ",good,dummy
        self._ok = good == i
        return good

    def matchLong(self,i):
        self._ok = False
        good = self.getLong()
        dummy = self.getLong()
        #print "\tmatching read ",good,dummy
        self._ok = good == i
        return good
    
    def matchString(self,s):
        self._ok = False
        good = self.getString()
        dummy = self.getString()
        self._ok = good == s
        return good
    
    def matchIntList(self,ilist):
        self._ok = False
        good = self.parseVI()
        dummy = self.parseVI()
        if ilist == None or type(ilist) != types.ListType:
            return good
        self._ok = len(good) == len(ilist)
        if not self._ok:
            return good
        self._ok = good == ilist
        return good
    
    def matchDoubleList(self,dlist):
        self._ok = False
        good = self.parseVD()
        dummy = self.parseVD()
        if dlist == None or type(dlist) != types.ListType:
            return good
        self._ok = len(good) == len(dlist)
        if not self._ok:
            return good
        self._ok = reduce( (lambda x,y: x and math.fabs(y[0]-y[1]) < 10e-5), zip(good,dlist),True)
        return good
        
        
    def matchStringList(self,slist):
        self._ok = False
        good = self.parseVS()
        dummy = self.parseVS()
        if slist == None or type(slist) != types.ListType:
            return good
        self._ok = len(good) == len(slist)
        if not self._ok:
            return good
        self._ok = good == slist
        return good
    
    def printString(self,f,s):
        str = "\"" + s + "\" "
        f.write(str)
        
    def printNum(self,f,i):
        str = "" + i + " "
        f.write(str)
    
    def printList(self,f,list):
        str = str(list)
        f.write(str)

    def __init__(self, path):
        '''
        Constructor
        '''
        self.file = open(path)
        lines = [str.rstrip() for str in self.file]
        toparse = StringIO.StringIO(' '.join(lines))
        self.tokenizer = tokenize.generate_tokens(toparse.readline)
        self._ok = False
        self.string = ''
        
#parse = Parse("input")
#count = 0
#while True:
#    list = parse.parseVS()
#
#    if list == None:
#        break
#    list2 = parse.parseVS()
#    list3 = parse.parseVS()
#    print count," ",list
#
#    
#    if list2 != list3:
#        print "mismatch on %s %s" % (str(list2), str(list3))
#    print "--------"
#    count += 1
    
        
