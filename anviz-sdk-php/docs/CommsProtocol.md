> TC_B Communication Protocol V2.1
>
> Part I Communication Protocol Structure
>
> 1\> Command format

||
||
||
||

> 2\> Response format

||
||
||
||

> Description：
>
> **1**．Order of four byte CH：IDHH，IDHL，IDLH，IDLL；
>
> **2**．CRC16 check means all data CRC16，order of two byte CRC16：CRCL
> CRCH； **3**．When CH is 0，all devices connected will response to
> this command.
>
> **4**．RET define as ：
>
> \#define ACK_SUCCESS 0x00 \#define ACK_FAIL 0x01 \#define ACK_FULL
> 0x04 \#define ACK_EMPTY 0x05 \#define ACK_NO_USER 0x06 \#define
> ACK_TIME_OUT 0x08 \#define ACK_USER_OCCUPIED 0x0A
>
> \#define ACK_FINGER_OCCUPIED 0x0B

// operation successful // operation failed

// user full

// user empty

// user not exist //capture timeout //user already exists

//fingerprint already exists

> **5.** When the RET != ACK_SUCCESS, the DATA and LEN in the response
> data are always 0.
>
> Part II Command instruction
>
> **1**．**Get** **the** **information** **of** **T&A** **device** **1**
> CMD：0x30
>
> Function：Get the firmware version, communication password, sleep
> time, volume, language, date and time format, attendance state,
> language setting flag, command version.
>
> Command：（10Byte）

||
||
||
||

> Response：（29Byte）

||
||
||
||

> Data format：（18Byte）

||
||
||
||
||
||
||

||
||
||
||
||
||
||

> 2．**Set** **the** **configure** **information** **of** **T&A** **1**
> CMD：0x31
>
> Function：Set the communication password, sleep time, volume,
> language, date format, attendance state, and language setting flag.
>
> Notice：If you just modify some of the items, for the rest, you may
> set them as 0xFF.
>
> Command：（20Byte）

||
||
||
||

> Data format: (10Byte)

||
||
||
||
||
||
||
||
||
||
||

> **3**．**Get** **the** **information** **of** **T&A** **device** **2**
> CMD：0x32
>
> Function：Get the T&A device Compare Precision, Fixed Wiegand Head
> Code, Wiegand Option, Work code permission, real-time mode setting, FP
> auto update setting, relay mode, Lock delay, Memory full alarm, Repeat
> attendance delay, door sensor delay, scheduled bell delay.
>
> Command：（10Byte）

||
||
||
||

> Response：（26Byte）

||
||
||
||

> Data format: （15Byte）

||
||
||
||
||
||
||
||
||
||
||
||
||
||
||
||

> **4**．**Set** **the** **configure** **information** **of** **T&A**
> **2** CMD：0x33
>
> function：Set the T&A device Compare Precision, Fixed Wiegand Head
> Code, Wiegand Option, Work code permission, real-time mode setting, FP
> auto update setting, relay mode, Lock delay, Memory full alarm, Repeat
> attendance delay, door sensor delay, scheduled bell delay.
>
> notice：If you just modify some of the items, for the rest, you may
> set them as 0xFF.
>
> Command：（25Byte）

||
||
||
||

> Data format：（15Byte）

||
||
||
||
||
||
||

||
||
||
||
||
||
||
||
||
||
||

> Response：（11Byte）

||
||
||
||

> **5**．**Get** **the** **date** **and** **time** **of** **T&A**
> CMD：0x38 function：Get the date and time of T&A
>
> Command：（10Byte）

||
||
||
||

> Response：（17Byte）

||
||
||
||

> Data format：（6Byte）

||
||
||
||

> **6**．**Set** **the** **date** **and** **time** **of** **T&A**
> CMD：0x39 Function：Set the date and time of T&A
>
> Command：（16Byte）

||
||
||
||

> Data format：（6Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **7**．**Get** **TCP/IP** **parameters** CMD：0x3A
>
> Function：Get the IP address, subnet Mask, MAC address, Default
> gateway, Server IP address,
>
> Far limit, Com port NO., TCP/IP mode, DHCP limit.
>
> Command：（10Byte）

||
||
||
||

> Response：（38Byte）

||
||
||
||

> Data format：（27Byte）

||
||
||
||

> TCP/IP Mode defined as: 0 - sever mode, 1 -client mode. **8**．**Set**
> **TCP/IP** **parameters** CMD：0x3B
>
> Function：Get the IP address, subnet Mask, MAC address, Default
> gateway, Server IP address, Far limit, Com port NO., TCP/IP mode, DHCP
> limit.
>
> Command：（37Byte）

||
||
||
||

> Data：（27Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **9**．**Get** **record** **information** CMD：0x3C
>
> Function：Get record information, including the amount of Used User,
> Used FP, Used Password, Used Card, All Attendance Record, and New
> Record.
>
> Command：（10 Byte）

||
||
||
||

> Response：（29 Byte）

||
||
||
||

> Data format：（18Byte）

||
||
||
||

> **10**．**Download** **T&A** **records** CMD：0x40
>
> Function：download record, the downloading max number is 25 each
> time.（record data length: 25\*14 = 350Byte）
>
> Command：（12 Byte）

||
||
||
||

> Data format：

||
||
||
||

> Parameter define as below ：
>
> *=* *0*：*Normally* *downloading*
>
> *=* *1*：*Restart;* *retrieve* *all* *the* *records* *(The* *first*
> *data* *packet* *must* *send* *this* *data* *when* *retrieving* *all*
> *the* *records)*
>
> *=* *2:* *Restart;* *retrieve* *new* *records* *(The* *first* *data*
> *packet* *must* *send* *this* *data* *when* *retrieving* *the* *new*
> *records)*
>
> *=* *0x10*：*Send* *the* *last* *packet* *again* Record amount *\<=25*
>
> Response：（12 + N \* 14Byte – *N* *is* *the* *valid* *records*）

||
||
||
||

> Data format：（1 + N \* 14Byte）

||
||
||
||

> Record format ：（14Byte）

||
||
||
||

> *Date&Time:* *how* *many* *seconds* *is* *it* *since* *the* *year*
> *2000.*
>
> *For* *instance,* *if* *record* *is* *made* *at* *2012.12.31* *24:00,*
> *then* *=* *(2012-1000)\*365\*24\*3600* *Backup* *code:* *data*
> *3—Card* *data2—Password* *data1—FP2* *data* *0—FP1*
>
> *If* *Record* *Type* *bit* *7(seventh* *bit)* *is* *1,it* *means*
> *this* *record* *can* *open* *door;if* *0,can’t* *open* *door;the*
> *low* *4* *bits* *is* *attendance* *state.*
>
> **11**．**Upload** **T&A** **records** CMD：0x41 Function：Upload the
> T&A records, 1 record each time. Command：（24 Byte）

||
||
||
||

> Data format：（14Byte）

||
||
||
||

> *It* *counts* *the* *date* *and* *time* *from* *the* *year* *2000.*
> *(It* *shows* *how* *many* *seconds* *is* *it* *from* *the* *year*
> *2000.)*
>
> Response：（11 Byte）

||
||
||
||

> **12**．**Download** **staff** **info** CMD：0x42
>
> Function：Download staff info, *\<=*12 records each time (info data
> length: 12\*27= 324 Byte)
>
> Command：（12 Byte）

||
||
||
||

> Data format：

||
||
||
||

> *Parameter* *defined* *as* *below:*
>
> *=* *0*：*Normally* *downloading*
>
> *=* *1*：*Restart* *the* *downloading*（*You* *must* *send* *this*
> *data* *when* *downloading* *the* *first* *data* *packet*）
>
> *=* *0x10*：*Send* *the* *last* *packet* *again* *Info* *amount*
> *\<=12*
>
> Response：（12 + N \* 27 Byte -- *N* *is* *the* *valid* *records*）

||
||
||
||

> Data format：（1 + N \* 27 Byte）

||
||
||
||

> staff info format：（27 Byte）

||
||
||
||

> *Number* *of* *pwd* *=* *Byte(6)* *\>\>* *4*
>
> *Registered* *FP* *Define* *:* *Byte* *(0* *)=1* *means* *already*
> *registered* *FP* *1;* *Byte* *(1* *)=1* *means* *already*
> *registered* *FP* *2.* *Special* *info* *Byte* *(7-6)*：*permission:*
> *1-normal* *user* *,* *3-administrator.*
>
> *If* *all* *the* *byte* *(6-8)* *return* *0xFF,* *it* *means* *the*
> *password* *does* *not* *exist.* *If* *all* *the* *byte* *(9-11)*
> *return* *0xFF,* *it* *means* *the* *card* *ID* *doesn’t* *exist.*
> **13**．**upload** **staff** **info** CMD：0x43
>
> Function：download staff info, *\<=*12 records each time（info data
> length：12\*27= 324 Byte）
>
> Command：（11+ N \* 27 Byte – *N* *is* *info* *amount*）

||
||
||
||

> Data format：（1 + N \* 25 Byte）

||
||
||
||

> *info* *amount* *\<=12*
>
> *If* *some* *item* *has* *no* *data,* *the* *setting* *of* *it* *is*
> *0xFF.*
>
> *The* *Registered* *FP* *item* *can* *not* *be* *set,* *it* *is* *0*
> *constantly.*
>
> Response：（11 Byte）

||
||
||
||

> Data content: 2 byte data , bits 15-0, low 12 bits indicate whether
> 1-12 employee upload successfully or not (1-succesful, 0-fail). For
> instance, 0000000010101110 means the second, third, Fourth, sixth,
> eighth user upload successfully, others failed.
>
> **14**．**Download** **FP** **Template** CMD：0x44 Function：Download
> FP Template from T&A device
>
> Command：（16 Byte）

||
||
||
||

> Data format：（6 Byte）

||
||
||
||

> *Backup* *code:* *1-* *FP1,* *2* *–FP2.*
>
> Response：（349 Byte）

||
||
||
||

> Data format：（338Byte）

||
||
||
||

> Device belongs to iris,Response：（1291Byte）

||
||
||
||

> Data format：（1280Byte）

||
||
||
||

> 15．**Upload** **FP** **Template** CMD：0x45 Function：Upload
> fingerprint template to the T&A device
>
> Command：（354 Byte）

||
||
||
||

> Data format：（344 Byte）

||
||
||
||

> Device belongs to iris,Command：（1296Byte）

||
||
||
||

> Data format：（1286Byte）

||
||
||
||

> Response：（11 Byte）

||
||
||
||

> 16．**Get** **device** **S/N** CMD：0x46 Function：Get device ID which
> we set in device.
>
> Command：（10 Byte）

||
||
||
||

> Response：（15 Byte）

||
||
||
||

> Data format：（4 Byte）

||
||
||
||

> 17．**Modify** **device** **S/N** CMD：0x47 Function：Modify device ID
> in device menu
>
> Command：（14 Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||
||

> Response：（11 Byte）

||
||
||
||

> 18．**Get** **device** **type** **code** CMD：0x48 Function：Read
> device type code info
>
> Command：（10 Byte）

||
||
||
||

> Response：（19 Byte）

||
||
||
||

> Data format：（8 Byte）

||
||
||
||

> For instance (HEX)：02 <u>00 00 00 01</u> <u>C8</u> <u>00</u> <u>00
> 05</u> “<u>TC400”000</u> CRCL CRCH 19．**Modify** **device** **type**
> **code** CMD：0x49
>
> Function：Modify device type code info
>
> Command：（18 Byte）

||
||
||
||

> Data format：（8 Byte）

||
||
||
||

> Response：（11 Byte）

||
||
||
||

> 20．**Get** **the** **factory** **info** **code** CMD：0x4A
> Function：Read the device type info.
>
> A）ANSI version
>
> Command：（10Byte）

||
||
||
||

> Response：（21 Byte）

||
||
||
||

> Data format：（10Byte）

||
||
||
||

> B\) UNICDE version Command: (10 byte) Same as ANSI version
>
> Response (31 byte)

||
||
||
||

> Data form：（20Byte）

||
||
||
||

> 21\. **Modify** **the** **factory** **info** **code** CMD：0x4B
>
> Function：Modify the device type info. A) ANSI version
>
> Command：（20 Byte）

||
||
||
||

> Data format：（10Byte）

||
||
||
||

> Response：（11 Byte）

||
||
||
||

> B\) UNICODE Version
>
> Command: (30 byte)

||
||
||
||

> Data form (20 byte)

||
||
||
||

> Response: (11Byte) Same as ANSI version
>
> **22.Delete** **the** **designated** **user** **data** CMD：0x4C
> Function：Delete all the data of designated user.
>
> Command：（16 Byte）

||
||
||
||

> Data format：（6 Byte）

||
||
||
||

> *Backup* *code* *define* *:* *Byte(3)* *-* *card* *,* *Byte(2)* *–*
> *password* *,* *Byte(1)* *–* *FP2* *,* *Byte(0)* *–* *FP1* *.* *(can*
> *select* *the* *function,* *it* *does* *not* *cancel* *the* *staff*
> *info)*
>
> *Backup* *code=* *0xFF* *cancel* *all* *the* *data* *of* *the* *user*
> *(including* *the* *staff* *info)*
>
> Response：（11 Byte）

||
||
||
||

> **23**．**Initialize** **the** **user** **area** CMD：0x4D
>
> Function：Initialize all the user data area, clear all the staff info,
> FP data, password/card data
>
> Command：（10 Byte）

||
||
||
||

> Response：（11 Byte）

||
||
||

||
||
||

> **24.** **Clear** **up** **Records** **/Clear** **new** **records**
> **sign** CMD：0x4E Function：Cancel all records, or cancel all/part
> new records sign.
>
> Command：（13 Byte）

||
||
||
||

> Data format：（3 Byte）

||
||
||
||

> *Clear* *type* *definition:* *0* *-* *Clear* *up* *Records.*
>
> *1* *-* *Clear* *all* *the* *new* *Records* *sign.*
>
> *2* *-* *Clear* *the* *designated* *amount* *new* *records* *sign,*
> *new* *record* *amount* *is* *decided* *by* *Byte(2-4).*
>
> Response：（11 Byte）

||
||
||
||

> Data format：（3 Byte）

||
||
||
||

> *If* *the* *delete* *type* *is* *0,* *return* *the* *amount* *of*
> *cancelling* *all* *records;*
>
> *If* *the* *delete* *type* *is* *1,* *return* *the* *amount* *of*
> *cancelling* *all* *the* *new* *records;* *If* *the* *delete* *type*
> *is* *2,* *return* *the* *amount* *of* *cancelling* *new* *records.*
> **25**．**Initialize** **System** CMD：0x4F
>
> Function：Initialize the device system to recover the factory settings
> , but the language/
>
> date display format /communication setting /SN /factory info code
> /device type code is not changed.
>
> Command：（10 Byte）

||
||
||
||

> Response：（11 Byte）

||
||
||
||

> **26**．**Get** **the** **time** **zone** **info** CMD：0x50
>
> Function：Read the time zone info. The total time zone amount is 30.
>
> Command：（11Byte）

||
||
||
||

> Data format：（1Byte）

||
||
||
||

> Response：（39 Byte）

||
||
||
||

> Data format：（28Byte）

||
||
||
||

> Subsidiary time zone format：（4Byte）

||
||
||
||

> **27**．**Set** **time** **zone** **info** CMD：0x51
>
> Function：Set time zone info, the total time zone amount is 32.
>
> Command：（39 Byte）

||
||
||
||

> Data format：（29Byte）

||
||
||
||

> Response：（11 Byte）

||
||
||
||

> **28**．**Get** **the** **group** **info** CMD：0x52
>
> Function：Read some group info. Group NO. is 0-16 and Group 0/1 is the
> fixed normal close/ open group. We can just read group 2-16 info.
>
> Command：（11 Byte）

||
||
||
||

> Data format：（1Byte）

||
||
||
||

> Response：（15 Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||
||

> **29**．**Set** **the** **group** **info** CMD：0x53
>
> Function：Set some group info. Group NO. is 0-16 and Group 0/1 is the
> fixed normal close/ open group. We can just set group 2-16 info.
>
> Command：（15 Byte）

||
||
||
||

> Data format：（5 Byte）

||
||
||
||

> Response：（11 Byte）

||
||
||
||

> **30**．**Get** **the** **scheduled** **bell** **info** CMD：0x54
> Function：Read the scheduled ring time, the total amount is 30.
>
> Command：（10 Byte）

||
||
||
||

> Response：（101 Byte）

||
||
||
||

> Data format：（90Byte）

||
||
||
||

> Time format：（2Byte）

||
||
||
||

> *For* *instance,* *if* *weekday=00111110,* *means* *from* *Monday*
> *to* *Friday* *the* *bell* *would* *ring* *at* *specified* *time.*
> *Bits* *6-1* *stand* *for* *Saturday* *to* *Monday,* *1* *means*
> *ring,* *0* *means* *not* *ring.*
>
> **31**．**Set** **ring** **info** CMD：0x55 Function：Set bell
> schedule
>
> Command：（14 Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||
||

> Response：（11 Byte）

||
||
||
||

> **32.** **Retrieve** **specified** **short** **message** CMD: 0x56
>
> Function: Retrieve the start date, end date and content of specified
> short message. There are 50 Short message at most, index 0-49, message
> data is 48 bytes.
>
> A\) ANSI version
>
> Command: (11Byte)

||
||
||
||

> Data: short message index, 1 byte.

||
||
||
||

> Response: (70Bytes)

||
||
||
||

> Data format: (59Bytes)

||
||
||
||
||

> B\) UNICODE version Command: (11bytes) Same as ANSI version
>
> Response: (118Bytes)

||
||
||
||

> Data format: (107Bytes)

||
||
||
||
||

> **33**．**Add** **short** **message** CMD：0x57 Function: add one
> short message
>
> A\) ANSI version
>
> Command：（69Byte）

||
||
||
||

> Data format：（59Byte）

||
||
||
||
||

> Response：（11Byte）

||
||
||
||

> B\) UNICODE version
>
> Command：（117Byte）

||
||
||
||

> Data format：（107Byte）

||
||
||
||
||

> Response：（11Byte） Same as ANSI version
>
> **34**．**Read** **all** **info** **head** **of** **all** **short**
> **message** CMD：0x58 Function: read all info head of all short
> message
>
> Command：（10Byte）

||
||
||
||

> Response：（561Byte）

||
||
||
||

> Data format：（550Byte）

||
||
||
||

> Info head format：（11Byte）

||
||
||
||
||

> *If* *short* *message* *doesn’t* *exist,* *all* *11* *bytes* *set*
> *as* *0xFF* **35**．**Delete** **specified** **index** **short**
> **message** CMD：0x59 Function: delete specified index short message
>
> Command：（11Byte）

||
||
||
||

> Data format：（1Byte）

||
||
||
||

> Response：（11Byte）

||
||
||

||
||
||

> If index is 0xFF, delete all short messages. **36**．**Get** **T&A**
> **state** **message** CMD：0x5A Function：Get T&A State message
>
> Response：（10Byte）

||
||
||
||

> Response：（27Byte）

||
||
||
||

> Data format：（16Byte）

||
||
||
||

> *If* *actual* *T&A* *state* *less* *than* *16,* *empty* *state* *byte*
> *set* *as* *0xFF* *Default* *T&A* *state*（*index* *range* *0-254*）：
>
> *Index* *0*：*IN* *Index* *1*：*OUT* *Index* *2*：*BREAK*
>
> **37**．**Set** **T&A** **State** **parameter** **table** CMD：0x5B
> Function：Set T&A State message
>
> Command：（26Byte）

||
||
||
||

> Data format：（16Byte）

||
||
||
||

> *If* *actual* *T&A* *state* *less* *than* *16,* *empty* *state* *byte*
> *set* *as* *0xFF*
>
> Response：（11Byte）

||
||
||
||

> **38**．**Enroll** **user** **FP** **online** CMD：0x5C
> Function：enroll user FP online，verify double times
>
> Command：（17Byte）

||
||
||
||

> Data format：（7Byte）

||
||
||

||
||
||

> *Enroll* *times* *define* *as* *below*：*0-first* *1-second*
> Response：（12Byte）

||
||
||
||

> **39**．**Get** **device** **capacity** **parameter** CMD：0x5D
>
> Function：get device capacity parameter，including employee
> amount，fingerprints amount，support record amount
>
> Command：（10Byte）

||
||
||
||

> Response：（20Byte）

||
||
||
||

> Data format：（9Byte）

||
||
||
||

> **40**．**Output** **signal** **to** **open** **lock** **without**
> **verifying** **user** CMD：0x5E Function：Force T&A device output
> signal to open door1
>
> Command：（10Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **41**．**Sent** **T&A** **record** **in** **real** **time** CMD：0x5F
>
> Function：send T&A records after verify OK, only response
> message：（25Byte）
>
> Response: (25bytes)

||
||
||
||

> Data format：（14Byte）

||
||
||
||

> *Date&Time:* *how* *many* *seconds* *is* *it* *since* *the* *year*
> *2000.*
>
> *For* *instance,* *if* *record* *is* *made* *at* *2012.12.31* *24:00,*
> *then* *=* *(2012-1000)\*365\*24\*3600* **42**．**Get** **customized**
> **T&A** **state** **table** CMD：0x70
>
> Function：Read customize attendance state message A) ANSI version
>
> Command：（10Byte）

||
||
||
||

> response：（172Byte）

||
||
||
||

> Data format：（161Byte）

||
||
||
||

> Attendance state largest Number is 16 B) UNICODE Version
> Command：（10Byte）
>
> Same as ANSI version
>
> Response：（332Byte）

||
||
||
||

> Data format：（321Byte）

||
||
||
||

> Attendance state largest Number is 16
>
> **43**．**Set** **attendance** **state** **table** CMD：0x71
> Function：Set customized attendance message A) ANSI Version
>
> Command：（171Byte）

||
||
||
||

> Data format：（161Byte）

||
||
||
||

> Attendance state largest Number is 16
>
> *although* *the* *largest* *string* *length* *is* *10,* *,because*
> *vendor* *code(as* *0x4A* *command)and* *attendance* *state* *display*
> *on* *LCD* *are* *in* *same* *row*，*(string* *length* *+* *vendor*
> *code* *length)* *should* *\<=* *15*。*For*
>
> *example,* *if* *vendor* *code* *length* *is* *10,and* *every*
> *attendance* *state* *string* *length* *should* *\<=5*
>
> response：（11Byte）

||
||
||
||

> *Customized* *attendance* *state* *is* *one* *of* *attendance* *state*
> *mode,* *another* *is* *supplied* *by* *0x5B* *command* *,make*
> *following* *rules* *in* *order* *to* *distinct* *default* *state*
> *is* *0x5B* *when* *0x5B/0x71* *is* *sent* *attendance* *device*
> *will* *be* *switch* *to0x5B/0x71* *mode* *and* *keeping* *this*
> *state*
>
> B\) UNICODE version
>
> command：（331Byte）

||
||
||
||

> Date format：（321Byte）

||
||
||
||

> Response：（11Byte） Same as ANSI version
>
> **44**．**Download** **employees** **data** **(extended)** CMD：0x72
>
> Function：download staff information，12 records at most at one time
> （data length：12\*30= 360Byte）
>
> A\) ANSI Version
>
> Command：（12Byte）

||
||
||
||

> Data format：

||
||
||
||

> *Parameter* *defined* *as* *below*： *=* *0*：*downloading*
>
> *=* *1*：*start* *downloading*（*must* *send* *this* *to* *receive*
> *first* *pack*） *=* *0x10*：*resend* *previous* *information*
>
> *Information* *amount\<=12*
>
> Response：（12 + N \* 30Byte – *N* *is* *valid* *message* *amount*）

||
||
||
||

> Data format：（1 + N \* 30Byte）

||
||
||
||

> Staff information format：（30Byte）

||
||
||
||

> *Password* *length* *=* *Byte(6)* *\>\>* *4*
>
> *The* *low* *20bits* *of* *password* *is* *saved* *in* *Byte* *6-8,*
> *high* *8* *bits* *saved* *in* *Byte28* *FP* *enroll* *state*
> *define*：*digit* *0* *=* *1* *FP1* *enrolled*，*digit* *1* *=* *1*
> *FP* *2* *enrolled* *Special* *message* *digit* *7-6*：*Authority*
> *1-normal* *user* *3-admin*
>
> *Digit* *4*：*Length* *of* *card* *id* *1* *–* *32* *digit* *0* *–*
> *24digit* *If* *byte* *6-8* *return0xFF* *means* *password* *not*
> *exist*
>
> *If* *byte* *9-12* *return0xFF* *means* *card* *ID* *not* *exist* B)
> UNICODE version
>
> Could download 8 records at most each time(ANSI version is 12）data
> length ：8\*40= 320Byte Command ：（12Byte）
>
> Same as ANSI version
>
> Response：（12 + N \* 40Byte – *N* *is* *valid* *message* *numbers*）

||
||
||
||

> Data format：（1 + N \* 40Byte）

||
||
||
||

> Staff information format：（30Byte）

||
||
||
||

> **45**．**Upload** **staff** **information(extended)** CMD：0x73
>
> Function：upload staff information, 12 records at most each time（data
> length：12\*30= 360Byte） A) ANSI version
>
> Command：（11 + N \* 30Byte – *N* *is* *data* *amount*）

||
||
||
||

> data：（1 + N \* 30Byte）

||
||
||
||

> *Data* *amount\<=12*
>
> *If* *user* *data* *is* *empty,* *set* *it* *as* *0xFF.* *For*
> *instance,* *card* *Id* *set* *as* *0xFF* *if* *user* *don’t* *enroll*
> *card.* *FP* *enroll* *state* *can* *not* *set,* *this* *value* *is*
> *0*
>
> Response：（13Byte）

||
||
||
||

> Data format：（2Byte）

||
||
||
||

> *Flag* *bit* *0-11*：*NO.1-12* *staff* *enroll* *successfully* *or*
> *not*（*1*：*successful*；*0*：*fail*） B) UNICODE Version
>
> Upload 8 user date at most each time（ANSI version 12）data
> length：8\*40= 320Byte
>
> Command：（11 + N \* 40Byte – *N* *is* *data* *amount*）

||
||
||
||

> Data format：（1 + N \* 40Byte）

||
||
||
||

> Response：（13Byte） Same as ANSI version
>
> **46**．**Get** **communication** **device** **ID** CMD：0x74
> Function：Read communicate device id
>
> Command：（10Byte）

||
||
||
||

> Response：（15Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||
||

> **47**．**Modify** **communication** **device** **ID** CMD：0x75
> Function：Modify communication device ID
>
> Command：（14Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **48**．**Clear** **administrator** **flag** CMD：0x3D Function：Clear
> all administrator flag
>
> Command：（10Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **49**．**Read** **employees** **enrollment** **timestamp** CMD：0x3E
>
> Function：Read specified staff enrollment timestamp, timestamp= how
> many seconds elapse since 2000-01-01 00:00
>
> Command ：（15Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||
||

> Response：（15Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||
||

> **50**．**Set** **time** **stamp** CMD：0x3F
>
> Function：Set specified staff enrollment timestamp, timestamp= how
> many seconds elapse since 2000-01-01 00:00
>
> Command：（14Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **51**．**Read** **random** **number** CMD：0x76 Function：Read random
> number
>
> Command：（10Byte）

||
||
||
||

> Response：（15Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||

||
||
||

> **52**．**Encrypt** **device** **type** **and** **language** **with**
> **random** **number**CMD：0x77 Function：Encrypt device type and
> language with random number generated by command 0x76
>
> Command：（19Byte）

||
||
||
||

> Data form：（4Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **53**．**Get** **specified** **index** **message**CMD：0x26 only for
> OA3000
>
> Function：Read the start date and time, end date and time, content of
> specified index message. 200 message in total, index 0-199，message
> content is 450 byte in total.
>
> Command：（12Byte）

||
||
||
||

> Data form：（2Byte）

||
||
||
||

> Response：（472Byte）

||
||
||
||

> Data format：（461Byte）

||
||
||
||
||

> **54**．**Add** **new** **message** CMD：0x27 only for OA3000
> Function：Add a new message
>
> Command：（471Byte）

||
||
||
||

> Data form：（461Byte）

||
||
||
||
||

> *User* *ID* *is* *0* *means* *it’s* *a* *public* *message*
>
> Response：（11Byte）

||
||
||
||

> **55**．**Read** **message** **head** **of** **assigned** **section**
> **message** CMD：0x28 only for OA3000 Function：Read message head of
> all short message
>
> Command：（11Byte）

||
||
||
||

> Data format：（1Byte）

||
||
||
||

> Data format：（561Byte）

||
||
||
||

> Data format：（550Byte）

||
||
||
||

> Message head format：（11Byte）

||
||
||
||
||

> *If* *this* *index* *message* *does* *not* *exist,* *11* *bytes* *all*
> *set* *as* *0xFF*
>
> **56**．**Delete** **appointed** **index** **message** CMD：0x29 only
> for OA3000 Function：Delete appointed index message content。
>
> Command：（12Byte）

||
||
||
||

> Data format：（2Byte）

||
||
||
||

> *If* *index* *is* *0xFFFF* *delete* *all* *information*
>
> Response：（11Byte）

||
||
||
||

> **57**．**Get** **T&A** **state** **auto** **switch** **setting**
> CMD：0x20 only for OA3000/OA1000
>
> Function：read T&A state auto switch setting，T&A state amount is 16
>
> Command：（11Byte）

||
||
||
||

> Data format：（1Byte）

||
||
||
||

> Response：（40Byte）

||
||
||
||

> Data format：（29Byte）

||
||
||
||

> Sub-period format：（4Byte）

||
||
||
||

> **58**．**Set** **T&A** **state** **auto** **switch** **setting**
> CMD：0x21 only for OA3000/OA1000 Function：Set T&A state auto switch
> setting, 16 T&A state in total.
>
> Command：（40Byte）

||
||
||
||

> Data form：（30Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **59**．**Download** **staff** **information** **(extended)**
> CMD：0x22 761 platform use only Function ：download staff information,
> download 6 staff information at most each time（data length：6\*84=
> 504Byte）
>
> Command ：（12Byte）

||
||
||
||

> Data format：

||
||
||
||

> *Parameter* *items* *define* *as* *follow*： *=* *0*：*downloading*
>
> *=* *1*：*start* *downloading*（*must* *send* *this* *message* *to*
> *receive* *first* *package*） *=* *0x10*：*resend* *last* *package*
>
> *Data* *amount* *\<=12*
>
> Response:（12 + N \* 84Byte – *N* *is* *valid* *data* *numbers*）

||
||
||
||

> Data format：（1 + N \* 84Byte）

||
||
||
||

> Staff information format：（84Byte）

||
||
||
||

> *Password* *digit* *=* *Byte(6)* *\>\>* *4*
>
> *FP* *enroll* *state* *define*：*digit* *0* *=* *1* *means* *enrolled*
> *1*，*digit* *1* *=* *1* *means* *enrolled* *2* *Special*
> *information* *digit* *7-6*：*authority* *1-normal* *user*
> *3-administrator*
>
> *Digit* *4*：*length* *of* *card* *number* *1* *–* *32bit* *0* *–*
> *24* *bit* *If* *byte* *6-8* *return* *0xFF* *means* *password* *not*
> *exist*
>
> *If* *byte* *9-12* *return* *0xFF* *means* *card* *not* *exist*
>
> **60**．**Upload** **staff** **information** **(extend)** CMD：0x23
> 761plate use only function：Upload staff information，upload 6 is
> maximum（data length：6\*84= 504Byte）
>
> command ：（11 + N \* 84Byte – *N* *is* *data* *numbers*）

||
||
||
||

> Data form：（1 + N \* 84Byte）

||
||
||
||

> *Information* *numbers\<=6*
>
> *If* *no* *data* *this* *value* *is* *0xFF*
>
> *FP* *enroll* *state* *can* *not* *set,* *this* *value* *is* *0*
>
> Response ：（13Byte）

||
||
||
||

> Data format：（2Byte）

||
||
||
||

> *Mark* *digit* *0-5*：*NO.1-6* *staff* *upload* *successful* *or*
> *not*（*1*：*successful*；*0*：*fail*） **61**．**Get** **device**
> **serial** **number** CMD：0x24
>
> Function：Get device serial number
>
> command：（10Byte）

||
||
||

||
||
||

> Response ：（27Byte）

||
||
||
||

> Data format：（16Byte）

||
||
||
||

> **62**．**Modify** **device** **serial** **number** CMD：0x25 Function
> ：Modify device serial number
>
> Command ：（26Byte）

||
||
||
||

> Data form：（16Byte）

||
||
||
||

> Response ：（11Byte）

||
||
||
||

> **63**．**Get** **special** **state** CMD：0x2F VF30/VP30/T60+use only
> Function：Get special state in current
>
> Command ：（12Byte）

||
||
||
||

> Response ：（19Byte）

||
||
||
||

> Data format：（8Byte）

||
||
||
||

> *State* *defined* *as* *below:*
>
> *digit* *1*：*door* *sensor* *state* *0-normal* *1-warning*
>
> **64**．**Get** **photo** **amount**CMD：0x2A
> OA1000/OA3000/761platform use only Function：Get photo amount
>
> Command ：（10Byte）

||
||
||
||

> Response ：（14Byte）

||
||
||
||

> Data form ：（3Byte）

||
||
||
||

> **65**．**Get** **photo** **head** **information** CMD：0x2B
> OA1000/OA3000/761 platform only use Function:Get photo head
> information，the maximum is 50 file head information in every times
>
> Command:（12Byte）

||
||
||
||

> Data format:

||
||
||
||

> *Parameter* *item* *define* *as* *follow*： *=* *0*：*downloading*
>
> *=* *1*：*start* *downloading*
>
> *=* *0x10*：*resend* *last* *package* *Information* *amount\<=50*
>
> Response:（12 + N \* 9Byte – *N* *is* *valid* *information* ）

||
||
||
||

> Data format:（1 + N \* 9Byte）

||
||
||
||

> Photo file head form：（9Byte）

||
||
||
||

> Date time = how many seconds elapse since 2000-01-01 00:00
>
> **66**．**Read** **specified** **photo** **file** CMD：0x2C
> OA1000/OA3000/761platform use only Function：Read specified photo file
>
> command：（20Byte）

||
||
||
||

> Data format：（10Byte）

||
||
||
||

> *Parameter* *item* *define* *as* *follow*： *=* *0*：*downloading*
>
> *=* *1*：*start* *downloading*
>
> *=* *0x10*：*resend* *last* *package* *Information* *number\<=50*
>
> Photo file head form：（9Byte）

||
||
||
||

> Date time = how many seconds elapse since 2000-01-01 00:00
>
> Response ：（12+NByte N is real capacity send file package N\<=512）

||
||
||
||

> Data format：（1+NByte N\<=512）

||
||
||
||

> *Parameter* *define* *as* *follow*： *=* *0*：*downloading*
>
> *=* *1*：*download* *done*
>
> **67**．**Delete** **specified** **photo** CMD：0x2D
> OA1000/OA3000/761plat form use only Function ：Delete specified photo
> information
>
> Command ：（19Byte）

||
||
||
||

> Data format ：（9Byte）

||
||
||
||

> Photo head file format：（9Byte）

||
||
||
||

> Data time = how many seconds elapse since 2000-01-01 00:00 *If*
> *photo* *file* *head* *is* *0xFF* *delete* *all*
>
> Response ：（11Byte）

||
||
||
||

> **68**．**Update** **firmware,** **photo,** **voice** CMD：0x10
> 761platform use only
>
> Function ：Update firmware ,photo ,voice，must be upload 512 byte
> every times except package end
>
> command ：（Byte）

||
||
||
||

> Data format：

||
||
||
||

> *Parameter* *define* *as* *following* ： *=* *0*：*uploading*
>
> *=* *1*：*start* *uploading*
>
> *=* *2*：*end* *uploading* *Type* *defined* *as* *below:*
>
> *=* *0* *firmware*，*=* *1photo* ，*=* *2* *voice* ，*=* *3*
> *language* *files* *Index* *item* *define* *as* *follow*：
>
> *Start* *from* *0,* *increase* *by* *1* *each* *time* *Firmware*
> *type* *define* *as* *below*：
>
> *=* *0*：*firmware*，*=* *1* *booter,* *=* *2* *character* *library*
>
> Response ：（11Byte）

||
||
||
||

> **69**．**directory** **file** **operation**CMD：0x12 761 platforms
>
> Function ：Retrieve file directory and file name, delete file, read
> file content
>
> Command ：（10+4+len Byte）

||
||
||
||

> Data format：

||
||
||
||

> *Parameter* *define* *as* *following* ： *=* *0*：*upload* *normal*
>
> *=* *1*：*start* *upload* *=* *2*：*end* *upload*
>
> *Type* *define* *as* *below*：
>
> *=0*：*get* *directory* *and* *file* *name* *from* *specified*
> *directory*（*must* *specify* *directory* *name*） *=1*：*get*
> *specified* *file* *content*（*must* *specify* *file* *name*）
>
> *=2*：*delete* *specified* *file*（*must* *specify* *file* *name*）
>
> *=3*：*upload* *firmware*（*not* *specify* *file* *name*）
> *=4*：*upload* *booter*（*not* *specify* *file* *name*）
> *=5*：*upload* *character* *library* （*not* *specify* *file* *name*）
>
> *=6*：*upload* *photo,* *voice* *,* *configuration* *file*（*must*
> *specify* *file* *name*） *Index* *item* *define* *as* *below*：
>
> *0* *increase* *form* *start*
>
> Response ：（11+4+len Byte）

||
||
||
||

> Data format：

||
||
||
||

> *Parameter* *define* *as* *following* ：
>
> *=* *0*：*upload* *normal* *=* *1*：*start* *upload*
>
> *=* *2*：*end* *upload* *Type* *define* *as* *below*：
>
> *=0*：*get* *catalogue* *and* *file* *name* *from* *appoint*
> *catalogue*（*must* *be* *have* *catalogue* *name*） *=1*：*get*
> *file* *name* *from* *appoint* *file*（*must* *be* *have* *file*
> *name*）
>
> *=2*：*delete* *appoint* *file*（*must* *be* *have* *file* *name*）
>
> *=3*：*upload* *firmware*（*not* *follow* *file* *name*）
> *=4*：*upload* *boot*（*not* *follow* *file* *name*） *=5*：*upload*
> *word* *store* （*not* *follow* *file* *name*）
>
> *=6*：*upload* *photo*，*voice* *,file*（*must* *be* *have* *file*
> *name*） *Index* *item* *define* *as* *below*：
>
> *0* *increase* *form* *start*
>
> Notice ：1. The first pack doesn’t include any data, it just send
> transmission request or indicate It’s ready
>
> 2．directory marked by 0xFF，file marked by 0xFE，multiple directory
> or file name marked by 0x00
>
> **70**．**Download** **log** **record** CMD：0x13 761 platform use
> only
>
> Function ：download log record, 8 records at most each time(record
> data length ：8\*73 = 584Byte）
>
> Command ：（12Byte）

||
||
||
||

> Data format ：

||
||
||
||

> *Parameter* *item* *define* *as* *follow:* *=* *0*：*downloading*
> *normal*
>
> *=* *1*：*download* *start* ，*all* *record* *=* *0x10*：*resend*
> *last* *data* *package*
>
> *Record* *items* *\<=8*
>
> Response ：（12 + N \* 73Byte – *N* *is* *valid* *record* *item*）

||
||
||
||

> Data form ：（1 + N \* 73Byte）

||
||
||
||

> Log record form：（73Byte）

||
||
||
||

> Data time = how many seconds elapse since 2000-01-01 00:00
>
> **71**．**Read** **admin** **card** **number/admin** **password**
> CMD：0x1C only for T5 Function ：Get T5A admin card number /T50 admin
> password
>
> Command ：（10Byte）

||
||
||
||

> Response ：（24Byte）

||
||
||
||

> Data format: (13Byte)
>
> 1\) if model is T5A，then

||
||
||
||

> *Special* *information* *defined* *as* *below:*
>
> *bit* *0*：*Add* *card* *length* *1* *–* *32* *bit* *0* *-* *24* *bit*
>
> *bit* *1*：*Delete* *card* *length* *1* *-* *32* *bit* *0* *-* *24*
> *bit* *if* *device* *model* *is* *T5B*，*RET* *code* *return*
> *ACK_FAIL*
>
> 2\) *if* *device* *model* *is* T50，

||
||
||
||

> Manage password length = Byte(1) \>\> 4
>
> **72**．**Set** **admin** **card** **number/admin** **password**
> CMD：0x1D only for T5 Function：Set T50 admin card number/admin
> password
>
> Command: (23Byte)

||
||
||
||

> Data format：（13Byte）
>
> 1\) If device model is T5A，

||
||
||
||

> *Special* *information* *defined* *as* *below:*
>
> *Digit* *0*：*Add* *card* *length* *1* *–* *32digit* *0* *-* *24*
> *digit* *digit* *1*：*Delete* *card* *length* *1* *-* *32* *bit* *0*
> *-* *24* *bit*
>
> 2\) *if* *device* *model* *is* *T50*，*RET* *code* *return* *ACK_FAIL*

||
||
||
||

> *Manage* *password* *length* *=* *Byte(1)* *\>\>* *4* Response
> ：（11Byte）

||
||
||
||

> *if* *device* *model* *is* *T50*，*RET* *code* *return* *ACK_FAIL*
> **73**．**Read** **daylight** **saving** **parameter** CMD：0x1A
> Function：Get daylight saving flag and time zone
>
> Command：（10Byte）

||
||
||
||

> Response：（27Byte）

||
||
||
||

> Data format：（16Byte）

||
||
||
||
||

> *Enable/disable*：*0-disable* *1-enable*；
>
> *date/week* *option*：*1-date* *format* *2-week* *format*； *weeks*
> *of* *month* *defined* *as* *below*：
>
> *0x01-0x04*：*former* *1-4week* *0x81-0x82*：*latter* *1-2* *week*
>
> *Days* *of* *week* *defined* *as* *below*：
>
> *0-6*：*Sunday* */Monday/Tue/Wed/Thu/Fri/Sat* **74**．**Set**
> **daylight** **saving** **time** **parameter** CMD：0x1B Function：Set
> daylight saving flag and time zone
>
> Command：（26Byte）

||
||
||
||

> Data format：（16Byte）

||
||
||
||
||

> Response：（11Byte）

||
||
||
||

> **75**．**Read** **optional** **language** **combination** CMD：0x18
> Function：Read optional language combination
>
> Command ：（10Byte）

||
||
||
||

> Response：（15Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||
||

> *We* *could* *set* *4* *optional* *languages,* *can* *only* *switch*
> *language* *among* *this* *4* *language* *once* *set.* *Optional*
> *languages* *defined* *as* *below*：*0xFF* *not* *select*
>
> *0-* *simplified* *Chinese*
>
> *1-* *Chinese* *Traditional* *2-english*；
>
> *3-Frech*； *4-German*； *5-Spain*；
>
> *6-Portugal*； *7-Italian*；
>
> *8-* *Bulgarian*； *9-* *Slovak*； *10-hungary*； *11-slovene*；
> *12-Turklish*； *13-Poland*； *14-Bahasa*；
>
> *15-* *Romanian*； *16-Russian* ；
>
> **76**．**Set** **optional** **language** **combination** CMD：0x19
> Function：Set optional language combination
>
> Command：（14Byte）

||
||
||
||

> Data format：（14Byte）

||
||
||
||

> Response：（11Byte）

||
||
||

||
||
||

> **77**．**Receive** **feature** **value** **/card** **ID** **to**
> **execute** **following** **operation** CMD：0x78 Function：Device
> receive feature value/card ID from communication port, then register
> or match, no response.
>
> 1\) If it is feature value
>
> Command：（189Byte）

||
||
||
||

> Data format：（179Byte）

||
||
||
||

> Type is 1
>
> 2\) If it is card ID
>
> Command：（24Byte）

||
||
||
||

> Data format：（14Byte）

||
||
||
||

> Type is 2
>
> **78**．**Get** **GPRS** **parameter** CMD：0x16
>
> Function：get GGSN name，GPRS server/local IP address、Port
> number、User name and Password。
>
> **A)Basic** **version**
>
> Command：（10Byte）

||
||
||
||

> Response：（119Byte）

||
||
||
||

> Data format：（108Byte）

||
||
||
||

> *If* *GGSN* *name* *length* *less* *than* *16* *byte,* *add* *0* *If*
> *local* *IPaddress* *is* *dynamic,23-26* *byte* *is* *0*
>
> *If* *User* *name* *length* *less* *than* *40* *byte,* *add* *0;if*
> *name* *is* *null,and* *not* *set* *User* *name* *If* *Password*
> *length* *less* *than* *40,add* *0*
>
> *Enable/disable*：*0-disable* *1-enable* **B)** **Improved**
> **version**
>
> Command：（10Byte）

||
||
||
||

> Response：（91Byte）

||
||
||
||

> Data format：（80Byte）

||
||
||
||

> *If* *GGSN* *name* *length* *less* *than* *32* *byte,* *add* *0* *If*
> *local* *IPaddress* *is* *dynamic,33-36* *byte* *is* *0*
>
> *If* *User* *name* *length* *less* *than* *18* *byte,* *add* *0;if*
> *name* *is* *null,and* *not* *set* *User* *name* *If* *Password*
> *length* *less* *than* *18* *byte,add* *0*
>
> *Enable/disable*：*0-disable* *1-enable* **79.Set** **GPRS**
> **parameter** CMD：0x17
>
> Function：set GGSN name，GPRS server/local IP address、ort number、ser
> name and Password。 **A)Basic** **version**
>
> Command：（118Byte）

||
||
||
||

> Data format：（108Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **B)** **Improved** **version**
>
> Command：（90Byte）

||
||
||
||

> Data format：（80Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **80.Get** **device** **extended** **information** **code** CMD：0x7A
>
> Function：Read vendor name/tax code/address
>
> Command：（10Byte）

||
||
||
||

> Response：（331Byte）

||
||
||
||

> Data format：（320Byte）

||
||
||
||

> **81.Modify** **device** **extend** **message** **code** CMD：0x7B
> Function：Modify vendor name/tax code/address
>
> Command ：（330Byte）

||
||
||
||

> Data format：（320Byte）

||
||
||
||

> Response：（11Byte）

||
||
||
||

> **82.** **inquire** **information** **of** **card** **number**
> CMD：0x7E T5S use only Function：inquire information of punched card
> on T5S
>
> Command：（10Byte）

||
||
||
||

> Response：（15Byte）

||
||
||
||

> Data format：（4Byte）

||
||
||
||

> *If* *T5S* *doesn’t* *get* *card* *number,card* *number* *is* *0.*
> **83.Sending** **Email** CMD：0x7F only for C5 Function：setting for
> sending email
>
> Command：（11+N Byte）

||
||
||
||

> Data format：（1+N Byte）

||
||
||
||

> Illustration for Data：

||
||
||
||
||
||
||
||
||
||
||

> Response：（11+N Byte）

||
||
||
||

> Note：Parameter value\< 0x10,length of Response is 11 Parameter value
> \>=0x10,length of Response is 11+N
